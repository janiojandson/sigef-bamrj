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
            if (empty($itens)) die("<script>alert('Selecione notas!'); history.back();</script>");

            $usuario = $_SESSION['username']; $perfil = $_SESSION['role']; $timestamp = date('d/m/Y H:i');
            $numero_rap = "RAP-" . date('Y') . "-" . strtoupper(substr(uniqid(), -4));

            try {
                $db->beginTransaction();
                $stmtRap = $db->prepare("INSERT INTO de_raps (numero_rap, criado_por) VALUES (?, ?) RETURNING id");
                $stmtRap->execute([$numero_rap, $usuario]); $rap_id = $stmtRap->fetchColumn();

                foreach ($itens as $item_id) {
                    $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); $stmtCur->execute([$item_id]); $fase_anterior = $stmtCur->fetchColumn();
                    $db->prepare("UPDATE de_itens SET status_atual = 'AGU_ASS_GESTOR_FINANCEIRO', observacao_atual = ?, rap_id = ? WHERE id = ?")->execute(["[$timestamp - $perfil]: GERAR_RAP - \"Lote $numero_rap\"", $rap_id, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'GERAR_RAP', ?, 'AGU_ASS_GESTOR_FINANCEIRO', 'Agrupado no RAP')")->execute([$item_id, $usuario, $perfil, $fase_anterior]);
                }
                $db->commit();
                // Item 3: Redireciona diretamente para a tela de impressão em vez de abrir popup invisível
                header("Location: /operador/imprimir_rap?id=$rap_id"); exit();
            } catch (\Exception $e) { $db->rollBack(); die("Erro Tático."); }
        }
    }

    public function processarAcao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0; $tipo_acao = $_POST['tipo_acao'] ?? ''; $observacao = trim($_POST['observacao'] ?? '');
            $usuario = $_SESSION['username']; $perfil = $_SESSION['role']; $timestamp = date('d/m/Y H:i');
            $novo_status = ''; $acao_log = ''; $tab = 'receber'; $update_fields = []; $update_values = [];

            if ($tipo_acao === 'receber') { $novo_status = 'AGUARDANDO_INSERCAO_NP'; $acao_log = 'RECEBER_EXEC_FIN'; $tab = 'receber'; } 
            elseif ($tipo_acao === 'inserir_np') { $novo_status = 'AGUARDANDO_INSERCAO_LF'; $acao_log = 'INSERIR_NP'; $tab = 'np'; $update_fields[] = 'np_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input'])); } 
            elseif ($tipo_acao === 'inserir_lf') { $novo_status = 'AGUARDANDO_ATENDIMENTO_FINANCEIRO'; $acao_log = 'INSERIR_LF'; $tab = 'lf'; $update_fields[] = 'lf_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input'])); } 
            elseif ($tipo_acao === 'atender_fin') { $novo_status = 'AGUARDANDO_INSERCAO_OP'; $acao_log = 'ATENDIMENTO_FINANCEIRO'; $tab = 'atendimento'; } 
            elseif ($tipo_acao === 'inserir_op') { $novo_status = 'AGUARDANDO_GERACAO_RAP'; $acao_log = 'INSERIR_OP'; $tab = 'op'; $update_fields[] = 'op_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input'])); } 
            elseif ($tipo_acao === 'inserir_ob') {
                $novo_status = 'ARQUIVADO'; $acao_log = 'INSERIR_OB_ARQUIVAR'; $tab = 'ob';
                $update_fields[] = 'ob_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input']));
                $update_fields[] = 'data_pagamento = ?'; $update_values[] = $_POST['data_pagamento'];
                
                // MULTIPLE FILE UPLOADS Mapeamento
                $arquivos_paths = [];
                if (isset($_FILES['ob_arquivo'])) {
                    $total = count($_FILES['ob_arquivo']['name']);
                    for ($f=0; $f < $total; $f++) {
                        if ($_FILES['ob_arquivo']['error'][$f] === UPLOAD_ERR_OK) {
                            $uploadDir = __DIR__ . '/../../public/uploads/ob/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                            $fileName = time() . '_' . $f . '_' . basename($_FILES['ob_arquivo']['name'][$f]);
                            if (move_uploaded_file($_FILES['ob_arquivo']['tmp_name'][$f], $uploadDir . $fileName)) { 
                                $arquivos_paths[] = '/uploads/ob/' . $fileName; 
                            }
                        }
                    }
                }
                if (!empty($arquivos_paths)) {
                    $update_fields[] = 'ob_arquivo = ?'; 
                    $update_values[] = implode('|', $arquivos_paths); // Salva os arquivos separados por pipe |
                }
                $observacao = "Processo arquivado.";
            } 
            elseif ($tipo_acao === 'rejeitar') { $novo_status = 'REJEITADO_EXEC_FIN'; $acao_log = 'REJEITAR_EXEC_FIN'; $tab = 'receber'; if(empty($observacao)) die("<script>alert('Justificativa obrigatória!'); history.back();</script>"); } 
            elseif ($tipo_acao === 'reiniciar') { $novo_status = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN'; $acao_log = 'REINICIAR_LIQUIDACAO'; $update_fields[] = 'np_numero = ?'; $update_values[] = null; $update_fields[] = 'lf_numero = ?'; $update_values[] = null; $update_fields[] = 'op_numero = ?'; $update_values[] = null; $observacao = "Liquidação reiniciada."; } 
            elseif ($tipo_acao === 'autorizar_cancelamento') { $novo_status = 'CANCELADO_PELA_ORIGEM'; $acao_log = 'AUTORIZAR_CANCELAMENTO'; $observacao = "Operador atestou baixa."; $tab = 'cancelar'; }

            if(empty($observacao)) $observacao = "Avanço de fase.";
            $obs_formatada = "[{$timestamp} - {$perfil}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); $stmtCur->execute([$item_id]); $fase_anterior = $stmtCur->fetchColumn();
                $sql_up = "UPDATE de_itens SET status_atual = ?, observacao_atual = ?"; $params_up = [$novo_status, $obs_formatada];
                if (!empty($update_fields)) { $sql_up .= ", " . implode(", ", $update_fields); $params_up = array_merge($params_up, $update_values); }
                $sql_up .= " WHERE id = ?"; $params_up[] = $item_id;
                $db->prepare($sql_up)->execute($params_up);
                $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$item_id, $usuario, $perfil, $acao_log, $fase_anterior, $novo_status, $observacao]);
                $db->commit(); header("Location: /operador/fila?tab=" . $tab); exit();
            } catch (\Exception $e) { $db->rollBack(); die("Erro Tático."); }
        }
    }

    public function excluirRap() {
        if (!isset($_SESSION['user_id'])) exit;
        $id = $_GET['id'] ?? 0;
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_GERACAO_RAP', rap_id = NULL WHERE rap_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM de_raps WHERE id = ?")->execute([$id]);
            $db->commit();
            header("Location: /operador/monitoramento");
            exit;
        } catch (\Exception $e) { $db->rollBack(); die("Erro ao excluir RAP."); }
    }
    
    public function monitoramento() {
        if (!isset($_SESSION['user_id'])) exit;
        $db = Database::getConnection();
        // 🛡️ ARQUIVADOS AGORA APARECEM NA QUERY
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual NOT IN ('EM_ELABORACAO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO') ORDER BY l.criado_em DESC";
        $itens_ativos = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $raps = $db->query("SELECT * FROM de_raps ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/operador_monitoramento.php';
    }

    public function imprimirRap() {
        if (!isset($_SESSION['user_id'])) exit;
        $id = $_GET['id'] ?? 0;
        $db = Database::getConnection();
        $rap = $db->prepare("SELECT * FROM de_raps WHERE id = ?");
        $rap->execute([$id]); $rap = $rap->fetch();
        if(!$rap) die("RAP não encontrado");
        $itens = $db->prepare("SELECT * FROM de_itens WHERE rap_id = ?");
        $itens->execute([$id]); $itens = $itens->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/imprimir_rap.php';
    }
}