<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class OperadorController { 
      
    public function fila() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $db = Database::getConnection(); 
        
        $fases = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB', 'AGUARDANDO_AVAL_CANCELAMENTO', 'REJEITADO_PELO_ASSINADOR']; 
        $in = str_repeat('?,', count($fases) - 1) . '?'; 
        
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual IN ($in) ORDER BY i.prioridade DESC, l.criado_em ASC"; 
        $stmt = $db->prepare($sql); 
        $stmt->execute($fases); 
        $todos_itens = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        $itens_receber = []; $itens_np = []; $itens_lf = []; $itens_atendimento = []; $itens_op = []; $itens_rap = []; $itens_ob = []; $itens_cancelar = []; 
        
        foreach ($todos_itens as $item) { 
            if (str_contains($item['status_atual'], 'RECEBIMENTO_EXEC_FIN') || str_contains($item['status_atual'], 'REJEITADO_PELO_ASSINADOR')) $itens_receber[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_NP') $itens_np[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_LF') $itens_lf[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_ATENDIMENTO_FINANCEIRO') $itens_atendimento[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_OP') $itens_op[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_GERACAO_RAP') $itens_rap[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_OB') $itens_ob[] = $item;  
            if ($item['status_atual'] === 'AGUARDANDO_AVAL_CANCELAMENTO') $itens_cancelar[] = $item; 
        } 
        
        $aba_ativa = $_GET['tab'] ?? 'receber'; 
        require __DIR__ . '/../views/operador_fila.php'; 
    } 

    public function gerarRapLote() { 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
            $db = Database::getConnection(); 
            $itens = $_POST['itens_selecionados'] ?? []; 
            if (empty($itens)) { echo "<script>alert('Selecione notas!'); history.back();</script>"; exit(); } 

            $usuario = $_SESSION['username']; $perfil = $_SESSION['role']; $timestamp = date('d/m/Y H:i'); 
            $numero_rap = "RAP-" . date('Y') . "-" . strtoupper(substr(uniqid(), -4)); 

            try { 
                $db->beginTransaction(); 
                $stmtRap = $db->prepare("INSERT INTO de_raps (numero_rap, criado_por) VALUES (?, ?) RETURNING id"); 
                $stmtRap->execute([$numero_rap, $usuario]); $rap_id = $stmtRap->fetchColumn(); 

                foreach ($itens as $item_id) { 
                    $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");  
                    $stmtCur->execute([$item_id]);  
                    $fase_anterior = $stmtCur->fetchColumn(); 
                    
                    $db->prepare("UPDATE de_itens SET status_atual = 'AGU_ASS_GESTOR_FINANCEIRO', observacao_atual = ?, rap_id = ? WHERE id = ?")->execute(["[$timestamp - $perfil]: GERAR_RAP - \"Lote $numero_rap\"", $rap_id, $item_id]); 
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'GERAR_RAP', ?, 'AGU_ASS_GESTOR_FINANCEIRO', 'Agrupado no RAP')")->execute([$item_id, $usuario, $perfil, $fase_anterior]); 
                } 
                $db->commit(); 
                // 🛡️ CORREÇÃO: Abre o PDF numa nova aba sem bugar
                echo "<script>window.open('/operador/imprimir_rap?id=$rap_id', '_blank'); window.location.href='/operador/fila?tab=rap';</script>"; exit(); 
            } catch (\Exception $e) { $db->rollBack(); die("Erro Tático: " . $e->getMessage()); } 
        } 
    } 

    public function processarAcao() { 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
            $db = Database::getConnection(); 
            $tipo_acao = $_POST['tipo_acao'] ?? '';  
            $observacao = trim($_POST['observacao'] ?? ''); 
            $usuario = $_SESSION['username'];  
            $perfil = $_SESSION['role'];  
            $timestamp = date('d/m/Y H:i'); 
            $tab = $_POST['tab_origem'] ?? 'receber'; 
            
            try { 
                $db->beginTransaction(); 

                // 📌 AÇÕES EM LOTE
                if (in_array($tipo_acao, ['receber', 'inserir_np', 'inserir_lf', 'atender_fin', 'inserir_op', 'autorizar_cancelamento'])) { 
                    $itens_selecionados = $_POST['itens_selecionados'] ?? []; 
                    if (empty($itens_selecionados)) {  
                        echo "<script>alert('Você deve marcar os itens na tabela!'); window.location.href='/operador/fila?tab={$tab}';</script>";  
                        exit();  
                    } 
                    
                    $valor_input = strtoupper(trim($_POST['valor_input'] ?? '')); 
                    $acao_log = strtoupper($tipo_acao); 

                    foreach ($itens_selecionados as $item_id) { 
                        $update_fields = []; $update_values = []; 

                        if ($tipo_acao === 'receber') { $novo_status = 'AGUARDANDO_INSERCAO_NP'; $tab = 'receber'; $observacao_atual = "Carga recebida."; } 
                        elseif ($tipo_acao === 'inserir_np') { $novo_status = 'AGUARDANDO_INSERCAO_LF'; $tab = 'np'; $update_fields[] = 'np_numero = ?'; $update_values[] = $valor_input; $observacao_atual = "NP $valor_input lançada."; } 
                        elseif ($tipo_acao === 'inserir_lf') { $novo_status = 'AGUARDANDO_ATENDIMENTO_FINANCEIRO'; $tab = 'lf'; $update_fields[] = 'lf_numero = ?'; $update_values[] = $valor_input; $observacao_atual = "LF $valor_input lançada."; } 
                        elseif ($tipo_acao === 'atender_fin') { $novo_status = 'AGUARDANDO_INSERCAO_OP'; $tab = 'atendimento'; $observacao_atual = "Atendimento Financeiro OK."; } 
                        elseif ($tipo_acao === 'inserir_op') { $novo_status = 'AGUARDANDO_GERACAO_RAP'; $tab = 'op'; $update_fields[] = 'op_numero = ?'; $update_values[] = $valor_input; $observacao_atual = "OP $valor_input lançada."; } 
                        elseif ($tipo_acao === 'autorizar_cancelamento') { $novo_status = 'CANCELADO_PELA_ORIGEM'; $tab = 'cancelar'; $observacao_atual = "Operador atestou baixa."; } 

                        $obs_formatada = "[{$timestamp} - {$perfil}]: {$acao_log} - \"{$observacao_atual}\""; 
                        
                        $stmtFase = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); 
                        $stmtFase->execute([$item_id]); 
                        $fase_anterior = $stmtFase->fetchColumn(); 
                        
                        $sql_up = "UPDATE de_itens SET status_atual = ?, observacao_atual = ?";  
                        $params_up = [$novo_status, $obs_formatada]; 
                        
                        if (!empty($update_fields)) {  
                            $sql_up .= ", " . implode(", ", $update_fields);  
                            $params_up = array_merge($params_up, $update_values);  
                        } 
                        $sql_up .= " WHERE id = ?";  
                        $params_up[] = $item_id; 
                        
                        $db->prepare($sql_up)->execute($params_up); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$item_id, $usuario, $perfil, $acao_log, $fase_anterior, $novo_status, $observacao_atual]); 
                    } 
                }  
                // 📌 AÇÕES INDIVIDUAIS (OB, Rejeitar, Reiniciar)
                else { 
                    $item_id = $_POST['item_id'] ?? 0; 
                    
                    $stmtFase = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); 
                    $stmtFase->execute([$item_id]); 
                    $fase_anterior = $stmtFase->fetchColumn(); 
                    
                    $update_fields = []; $update_values = []; 

                    // 🛡️ CORREÇÃO DE UPLOAD DA OB E LOG DA OB
                    if ($tipo_acao === 'inserir_ob') { 
                        $novo_status = 'ARQUIVADO'; $acao_log = 'INSERIR_OB_ARQUIVAR'; $tab = 'ob'; 
                        $numero_ob = strtoupper(trim($_POST['valor_input'] ?? '')); 
                        $update_fields[] = 'ob_numero = ?'; $update_values[] = $numero_ob; 
                        $update_fields[] = 'data_pagamento = ?'; $update_values[] = $_POST['data_pagamento']; 
                        
                        if (isset($_FILES['ob_arquivo']) && $_FILES['ob_arquivo']['error'] === UPLOAD_ERR_OK) { 
                            $uploadDir = __DIR__ . '/../../public/uploads/ob/';  
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); 
                            
                            $ob_limpa = preg_replace('/[^A-Za-z0-9]/', '', $numero_ob);  
                            $ext = pathinfo($_FILES['ob_arquivo']['name'], PATHINFO_EXTENSION); 
                            $fileName = 'OB_' . $ob_limpa . '_' . $item_id . '_' . time() . '.' . $ext; 
                            
                            if (move_uploaded_file($_FILES['ob_arquivo']['tmp_name'], $uploadDir . $fileName)) { 
                                $update_fields[] = 'ob_arquivo = ?';  
                                $update_values[] = '/uploads/ob/' . $fileName; 
                            } 
                        } 
                        $observacao = "OB {$numero_ob} liquidada e arquivada com sucesso."; 
                    }  
                    elseif ($tipo_acao === 'rejeitar') { 
                        $novo_status = 'REJEITADO_EXEC_FIN'; $acao_log = 'REJEITAR_EXEC_FIN'; $tab = 'receber'; 
                        if(empty($observacao)) die("<script>alert('Justificativa obrigatória!'); history.back();</script>"); 
                    }  
                    elseif ($tipo_acao === 'reiniciar') { 
                        $novo_status = 'AGUARDANDO_INSERCAO_NP'; $acao_log = 'REINICIAR_LIQUIDACAO'; $tab = 'receber';
                        $update_fields[] = 'np_numero = ?'; $update_values[] = null; 
                        $update_fields[] = 'lf_numero = ?'; $update_values[] = null; 
                        $update_fields[] = 'op_numero = ?'; $update_values[] = null; 
                        $update_fields[] = 'rap_id = ?'; $update_values[] = null; 
                        $observacao = "Liquidação resetada (Dados anteriores apagados)."; 
                    }  
                    else {
                        die("Ação não reconhecida.");
                    }

                    if(empty($observacao)) $observacao = "Avanço de fase."; 
                    $obs_formatada = "[{$timestamp} - {$perfil}]: {$acao_log} - \"{$observacao}\""; 

                    $sql_up = "UPDATE de_itens SET status_atual = ?, observacao_atual = ?"; $params_up = [$novo_status, $obs_formatada]; 

                    if (!empty($update_fields)) { $sql_up .= ", " . implode(", ", $update_fields); $params_up = array_merge($params_up, $update_values); } 
                    $sql_up .= " WHERE id = ?"; $params_up[] = $item_id; 
                    
                    $db->prepare($sql_up)->execute($params_up); 
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$item_id, $usuario, $perfil, $acao_log, $fase_anterior, $novo_status, $observacao]); 
                } 

                $db->commit();  
                header("Location: /operador/fila?tab=" . $tab); exit(); 
            } catch (\Exception $e) { $db->rollBack(); die("Erro Tático: " . $e->getMessage()); } 
        } 
    } 

    public function monitoramento() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $db = Database::getConnection(); 
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual NOT IN ('EM_ELABORACAO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO') ORDER BY i.status_atual ASC, l.criado_em DESC"; 
        $itens_ativos = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
        $sqlRaps = "SELECT DISTINCT r.* FROM de_raps r JOIN de_itens i ON r.id = i.rap_id WHERE i.status_atual NOT IN ('ARQUIVADO', 'REJEITADO_EXEC_FIN', 'AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'CANCELADO_PELA_ORIGEM') ORDER BY r.id DESC"; 
        $raps = $db->query($sqlRaps)->fetchAll(PDO::FETCH_ASSOC); 
        require __DIR__ . '/../views/operador_monitoramento.php'; 
    } 

    public function excluirRap() { 
        if (!isset($_SESSION['user_id'])) exit; 
        $id = $_GET['id'] ?? 0; $db = Database::getConnection(); 
        try { 
            $db->beginTransaction(); 
            $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_GERACAO_RAP', rap_id = NULL WHERE rap_id = ? AND status_atual = 'AGU_ASS_GESTOR_FINANCEIRO'")->execute([$id]); 
            $stmt = $db->prepare("SELECT COUNT(*) FROM de_itens WHERE rap_id = ?"); 
            $stmt->execute([$id]); 
            if ($stmt->fetchColumn() == 0) { $db->prepare("DELETE FROM de_raps WHERE id = ?")->execute([$id]); } 
            $db->commit(); header("Location: /operador/monitoramento"); exit; 
        } catch (\Exception $e) { $db->rollBack(); die("Erro ao excluir RAP."); } 
    } 

    public function imprimirRap() { 
        if (!isset($_SESSION['user_id'])) exit; 
        $id = $_GET['id'] ?? 0; $db = Database::getConnection(); 
        $rap = $db->prepare("SELECT * FROM de_raps WHERE id = ?"); 
        $rap->execute([$id]); $rap = $rap->fetch(); 
        if(!$rap) die("RAP não encontrado"); 
        $itens = $db->prepare("SELECT * FROM de_itens WHERE rap_id = ?"); 
        $itens->execute([$id]); $itens = $itens->fetchAll(PDO::FETCH_ASSOC); 
        require __DIR__ . '/../views/imprimir_rap.php'; 
    } 
}
