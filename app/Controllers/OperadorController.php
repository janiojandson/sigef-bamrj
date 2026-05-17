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
        
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual IN ($in) ORDER BY i.prioridade DESC, i.op_numero ASC NULLS LAST, l.criado_em ASC"; 
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
                    $fase_atual = $stmtCur->fetchColumn(); 

                    if ($fase_atual !== 'AGUARDANDO_GERACAO_RAP') { 
                        $db->rollBack(); 
                        die("<script>alert('Item #{$item_id} não está mais disponível para RAP.'); history.back();</script>"); 
                    } 

                    $nova_fase = 'AGU_ASS_GESTOR_FINANCEIRO'; 
                    $obs = "[{$timestamp} - {$perfil}]: RAP Gerado ({$numero_rap}) — Encaminhado para assinatura do Gestor Financeiro."; 

                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ?, rap_id = ? WHERE id = ?")
                       ->execute([$nova_fase, $obs, $rap_id, $item_id]); 
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'GERAR_RAP', ?, ?)")
                       ->execute([$item_id, $usuario, $perfil, $nova_fase, $numero_rap]); 
                } 

                $db->commit(); 
                header("Location: /operador/fila?tab=rap"); exit(); 
            } catch (Exception $e) { 
                $db->rollBack(); 
                die("Erro ao gerar RAP: " . $e->getMessage()); 
            } 
        } 
    } 

    public function processarAcao() { 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
            $db = Database::getConnection(); 
            $tipo_acao = $_POST['tipo_acao'] ?? ''; 
            $itens = $_POST['itens_selecionados'] ?? []; 
            $valor_input = trim($_POST['valor_input'] ?? ''); 
            $tab_origem = $_POST['tab_origem'] ?? 'receber';
            $data_pagamento = trim($_POST['data_pagamento'] ?? '');
            
            if (empty($itens)) { 
                echo "<script>alert('Selecione pelo menos um item!'); history.back();</script>"; exit(); 
            } 

            $usuario = $_SESSION['username']; 
            $perfil = $_SESSION['role']; 
            $timestamp = date('d/m/Y H:i'); 

            try { 
                $db->beginTransaction(); 

                foreach ($itens as $item_id) { 
                    $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); 
                    $stmtCur->execute([$item_id]); 
                    $fase_atual = $stmtCur->fetchColumn(); 

                    switch ($tipo_acao) { 
                        case 'receber': 
                            if (!in_array($fase_atual, ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'REJEITADO_PELO_ASSINADOR'])) { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'AGUARDANDO_INSERCAO_NP'; 
                            $obs = "[{$timestamp} - {$perfil}]: RECEBIDO na Execução Financeira."; 
                            break; 
                        case 'inserir_np': 
                            if ($fase_atual !== 'AGUARDANDO_INSERCAO_NP') { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'AGUARDANDO_INSERCAO_LF'; 
                            $obs = "[{$timestamp} - {$perfil}]: NP Inserida — {$valor_input}."; 
                            $db->prepare("UPDATE de_itens SET np_numero = ? WHERE id = ?")->execute([$valor_input, $item_id]); 
                            break; 
                        case 'inserir_lf': 
                            if ($fase_atual !== 'AGUARDANDO_INSERCAO_LF') { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'AGUARDANDO_ATENDIMENTO_FINANCEIRO'; 
                            $obs = "[{$timestamp} - {$perfil}]: LF Inserida — {$valor_input}."; 
                            $db->prepare("UPDATE de_itens SET lf_numero = ? WHERE id = ?")->execute([$valor_input, $item_id]); 
                            break; 
                        case 'atender_fin': 
                            if ($fase_atual !== 'AGUARDANDO_ATENDIMENTO_FINANCEIRO') { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'AGUARDANDO_INSERCAO_OP'; 
                            $obs = "[{$timestamp} - {$perfil}]: Atendimento Financeiro Concluído."; 
                            break; 
                        case 'inserir_op': 
                            if ($fase_atual !== 'AGUARDANDO_INSERCAO_OP') { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'AGUARDANDO_GERACAO_RAP'; 
                            $obs = "[{$timestamp} - {$perfil}]: OP Inserida — {$valor_input}."; 
                            $db->prepare("UPDATE de_itens SET op_numero = ? WHERE id = ?")->execute([$valor_input, $item_id]); 
                            break; 
                        case 'inserir_ob': 
                            if ($fase_atual !== 'AGUARDANDO_INSERCAO_OB') { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'ARQUIVADO'; 
                            $obs = "[{$timestamp} - {$perfil}]: OB Inserida — {$valor_input}. Processo ARQUIVADO."; 
                            
                            // 🏦 FASE 1: Processa upload do comprovativo da OB (Múltiplos arquivos)
                            $ob_arquivos = [];
                            if (isset($_FILES['ob_comprovativo']) && !empty($_FILES['ob_comprovativo']['name'][0])) {
                                $ano_atual = date('Y');
                                $upload_dir = __DIR__ . "/../../public/uploads/ob/{$ano_atual}";
                                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                                
                                $total_files = is_array($_FILES['ob_comprovativo']['name']) ? count($_FILES['ob_comprovativo']['name']) : 1;
                                
                                // Adaptação para single ou array upload
                                $files_name = (array)$_FILES['ob_comprovativo']['name'];
                                $files_tmp = (array)$_FILES['ob_comprovativo']['tmp_name'];
                                $files_err = (array)$_FILES['ob_comprovativo']['error'];

                                for ($i = 0; $i < $total_files; $i++) {
                                    if ($files_err[$i] === UPLOAD_ERR_OK) {
                                        $ext = strtolower(pathinfo($files_name[$i], PATHINFO_EXTENSION));
                                        $nome_seguro = "OB_{$item_id}_" . date('Ymd_His') . "_{$i}." . $ext;
                                        $caminho_completo = "{$upload_dir}/{$nome_seguro}";
                                        if (move_uploaded_file($files_tmp[$i], $caminho_completo)) {
                                            $ob_arquivos[] = "uploads/ob/{$ano_atual}/{$nome_seguro}";
                                        }
                                    }
                                }
                            }
                            $ob_arquivo_str = !empty($ob_arquivos) ? implode(',', $ob_arquivos) : null;
                            
                            // Atualiza OB com número, data de pagamento, arquivo e status ARQUIVADO
                            $db->prepare("UPDATE de_itens SET ob_numero = ?, data_pagamento = ?, ob_arquivo = ?, status_atual = ?, observacao_atual = ? WHERE id = ?")
                               ->execute([$valor_input, $data_pagamento ?: null, $ob_arquivo_str, $nova_fase, $obs, $item_id]);
                            $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'INSERIR_OB', ?, ?)")
                               ->execute([$item_id, $usuario, $perfil, $nova_fase, $valor_input]);
                            continue 2; // Pula o update genérico abaixo
                            
                        case 'autorizar_cancelamento': 
                            if ($fase_atual !== 'AGUARDANDO_AVAL_CANCELAMENTO') { $db->rollBack(); die("<script>alert('Item #{$item_id} não está mais disponível.'); history.back();</script>"); } 
                            $nova_fase = 'CANCELADO'; 
                            $obs = "[{$timestamp} - {$perfil}]: Cancelamento Autorizado."; 
                            break; 
                        case 'rejeitar': 
                            $nova_fase = 'REJEITADO_EXEC_FIN'; 
                            if(empty($valor_input)) { $db->rollBack(); die("<script>alert('Justificativa obrigatória!'); history.back();</script>"); }
                            $obs = "[{$timestamp} - {$perfil}]: DEVOLVIDO OMAP — Motivo: {$valor_input}"; 
                            break; 
                        case 'reiniciar_custom':
                            $keep_np = isset($_POST['keep_np']);
                            $keep_lf = isset($_POST['keep_lf']);
                            $keep_op = isset($_POST['keep_op']);
                            
                            $nova_fase = 'AGUARDANDO_INSERCAO_NP';
                            $set_np = "np_numero = NULL";
                            $set_lf = "lf_numero = NULL";
                            $set_op = "op_numero = NULL";
                            
                            if ($keep_op && $keep_lf && $keep_np) {
                                $nova_fase = 'AGUARDANDO_GERACAO_RAP';
                                $set_np = "np_numero = np_numero";
                                $set_lf = "lf_numero = lf_numero";
                                $set_op = "op_numero = op_numero";
                            } elseif ($keep_lf && $keep_np) {
                                $nova_fase = 'AGUARDANDO_INSERCAO_OP';
                                $set_np = "np_numero = np_numero";
                                $set_lf = "lf_numero = lf_numero";
                            } elseif ($keep_np) {
                                $nova_fase = 'AGUARDANDO_INSERCAO_LF';
                                $set_np = "np_numero = np_numero";
                            }
                            
                            $obs = "[{$timestamp} - {$perfil}]: Processo reiniciado com manutenção de dados. Retornado para a fase correspondente.";
                            $db->prepare("UPDATE de_itens SET $set_np, $set_lf, $set_op, rap_id = NULL, ob_numero = NULL, ob_arquivo = NULL, data_pagamento = NULL WHERE id = ?")->execute([$item_id]);
                            break;
                        case 'reiniciar': 
                            $nova_fase = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN'; 
                            $obs = "[{$timestamp} - {$perfil}]: Liquidação resetada (Dados anteriores apagados). Retornado para caixa de entrada."; 
                            $db->prepare("UPDATE de_itens SET np_numero = NULL, lf_numero = NULL, op_numero = NULL, rap_id = NULL, ob_numero = NULL, ob_arquivo = NULL, data_pagamento = NULL WHERE id = ?")->execute([$item_id]); 
                            break;
                        case 'pular_para_ob':
                            if ($fase_atual !== 'AGUARDANDO_INSERCAO_OP') { $db->rollBack(); die("<script>alert('Item não está na OP.'); history.back();</script>"); }
                            $nova_fase = 'AGUARDANDO_INSERCAO_OB';
                            $obs = "[{$timestamp} - {$perfil}]: Ajuste — Documento encaminhado direto para OB.";
                            break;
                        default: 
                            $db->rollBack(); 
                            die("<script>alert('Ação desconhecida.'); history.back();</script>"); 
                    } 

                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                       ->execute([$nova_fase, $obs, $item_id]); 
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?)")
                       ->execute([$item_id, $usuario, $perfil, strtoupper($tipo_acao), $nova_fase, $valor_input ?: 'Sem detalhes']); 
                } 

                $db->commit(); 
                header("Location: /operador/fila?tab={$tab_origem}"); exit(); 
            } catch (Exception $e) { 
                $db->rollBack(); 
                die("Erro ao processar ação: " . $e->getMessage()); 
            } 
        } 
    } 

    /**
     * 📊 FASE 2: Monitoramento Global — Apenas RAPs ATIVOS
     * Exclui explicitamente processos com status ARQUIVADO ou CANCELADO
     */
    public function monitoramento() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $db = Database::getConnection(); 

        // 🧹 FASE 2: Lista APENAS RAPs cujos itens NÃO estão ARQUIVADOS ou CANCELADOS
        $sqlRaps = "SELECT r.* FROM de_raps r 
                    WHERE r.id IN (
                        SELECT DISTINCT i.rap_id FROM de_itens i 
                        WHERE i.rap_id IS NOT NULL 
                        AND i.status_atual NOT IN ('ARQUIVADO', 'CANCELADO')
                    )
                    ORDER BY r.criado_em DESC"; 
        $stmtRaps = $db->query($sqlRaps); 
        $raps = $stmtRaps ? $stmtRaps->fetchAll(PDO::FETCH_ASSOC) : []; 

        // 🧹 FASE 2: Lista APENAS itens que NÃO estão ARQUIVADOS ou CANCELADOS
        $sqlItens = "SELECT i.*, l.numero_geral, l.origem_tipo, r.numero_rap 
                     FROM de_itens i 
                     JOIN de_lotes l ON i.lote_id = l.id 
                     LEFT JOIN de_raps r ON i.rap_id = r.id 
                     WHERE i.status_atual NOT IN ('ARQUIVADO', 'CANCELADO')
                     ORDER BY i.prioridade DESC, i.op_numero ASC NULLS LAST, i.id ASC"; 
        $stmtItens = $db->query($sqlItens); 
        $todos_itens = $stmtItens ? $stmtItens->fetchAll(PDO::FETCH_ASSOC) : []; 

        require __DIR__ . '/../views/operador_monitoramento.php'; 
    } 

    /**
     * 🖨️ FASE 3: Imprimir RAP — Ordenação explícita por op_numero ASC
     */
    public function imprimirRap() { 
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); } 
        $id = $_GET['id'] ?? 0; 
        $db = Database::getConnection(); 

        $stmt = $db->prepare("SELECT * FROM de_raps WHERE id = ?"); 
        $stmt->execute([$id]); 
        $rap = $stmt->fetch(PDO::FETCH_ASSOC); 
        if (!$rap) die("RAP não encontrado."); 

        // 📋 FASE 3: ORDER BY LENGTH(op_numero) ASC, op_numero ASC — garante ordenação natural (1, 2, 10)
        $stmtItens = $db->prepare("SELECT * FROM de_itens WHERE rap_id = ? ORDER BY LENGTH(op_numero) ASC NULLS LAST, op_numero ASC NULLS LAST, id ASC"); 
        $stmtItens->execute([$id]); 
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC); 

        require __DIR__ . '/../views/imprimir_rap.php'; 
    } 

    public function excluirRap() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $id = $_GET['id'] ?? 0; 
        $db = Database::getConnection(); 

        try { 
            $db->beginTransaction(); 

            // Volta os itens que AINDA NÃO foram assinados para a fase de geração de RAP
            $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_GERACAO_RAP', rap_id = NULL, observacao_atual = ? WHERE rap_id = ? AND status_atual = 'AGU_ASS_GESTOR_FINANCEIRO'")
               ->execute(["[" . date('d/m/Y H:i') . " - Operador]: RAP Cancelado — Item retornou à fila de geração.", $id]); 

            // Remove o RAP
            $db->prepare("DELETE FROM de_raps WHERE id = ?")->execute([$id]); 

            $db->commit(); 
        } catch (Exception $e) { 
            $db->rollBack(); 
            die("Erro ao excluir RAP: " . $e->getMessage()); 
        } 

        header("Location: /operador/monitoramento"); exit(); 
    } 
}