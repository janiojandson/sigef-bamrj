<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class OperadorController {
    
    public function fila() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); }
        $db = Database::getConnection();
        
        $fases = [
            'AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 
            'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 
            'AGUARDANDO_INSERCAO_OB'
        ];
        $in = str_repeat('?,', count($fases) - 1) . '?';
        
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual IN ($in) ORDER BY i.prioridade DESC, l.criado_em ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($fases);
        $todos_itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $itens_receber = []; $itens_np = []; $itens_lf = []; $itens_atendimento = []; $itens_op = []; $itens_rap = []; $itens_ob = [];
        
        foreach ($todos_itens as $item) {
            if ($item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_EXEC_FIN') $itens_receber[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_NP') $itens_np[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_LF') $itens_lf[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_ATENDIMENTO_FINANCEIRO') $itens_atendimento[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_OP') $itens_op[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_GERACAO_RAP') $itens_rap[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_OB') $itens_ob[] = $item; 
        }
        require __DIR__ . '/../views/operador_fila.php';
    }

    public function processarAcao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $tipo_acao = $_POST['tipo_acao'] ?? ''; 
            $observacao = trim($_POST['observacao'] ?? '');
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');

            $novo_status = ''; $acao_log = ''; 
            $update_fields = []; $update_values = [];

            if ($tipo_acao === 'receber') {
                $novo_status = 'AGUARDANDO_INSERCAO_NP'; $acao_log = 'RECEBER_EXEC_FIN';
            } elseif ($tipo_acao === 'inserir_np') {
                $novo_status = 'AGUARDANDO_INSERCAO_LF'; $acao_log = 'INSERIR_NP';
                $update_fields[] = 'np_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input']));
            } elseif ($tipo_acao === 'inserir_lf') {
                $novo_status = 'AGUARDANDO_ATENDIMENTO_FINANCEIRO'; $acao_log = 'INSERIR_LF';
                $update_fields[] = 'lf_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input']));
            } elseif ($tipo_acao === 'atender_fin') {
                $novo_status = 'AGUARDANDO_INSERCAO_OP'; $acao_log = 'ATENDIMENTO_FINANCEIRO';
            } elseif ($tipo_acao === 'inserir_op') {
                $novo_status = 'AGUARDANDO_GERACAO_RAP'; $acao_log = 'INSERIR_OP';
                $update_fields[] = 'op_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input']));
            } elseif ($tipo_acao === 'gerar_rap') {
                $novo_status = 'AGU_ASS_GESTOR_FINANCEIRO'; $acao_log = 'GERAR_RAP_ENVIAR_ASSINATURA'; // 🛡️ VAI PRO GESTOR
                $observacao = "Enviado ao Gestor Financeiro.";
            } elseif ($tipo_acao === 'inserir_ob') {
                $novo_status = 'ARQUIVADO'; $acao_log = 'INSERIR_OB_ARQUIVAR';
                $update_fields[] = 'ob_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input']));
                $update_fields[] = 'data_pagamento = ?'; $update_values[] = $_POST['data_pagamento'];
                $observacao = "Processo arquivado.";
            } elseif ($tipo_acao === 'rejeitar') {
                $novo_status = 'REJEITADO_EXEC_FIN'; $acao_log = 'REJEITAR_EXEC_FIN';
                if(empty($observacao)) die("<script>alert('A justificativa de rejeição é obrigatória!'); history.back();</script>");
            } elseif ($tipo_acao === 'reiniciar') { // 🛡️ NOVA AÇÃO: REINICIAR LIQUIDAÇÃO
                $novo_status = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN'; $acao_log = 'REINICIAR_LIQUIDACAO';
                $update_fields[] = 'np_numero = ?'; $update_values[] = null;
                $update_fields[] = 'lf_numero = ?'; $update_values[] = null;
                $update_fields[] = 'op_numero = ?'; $update_values[] = null;
                $observacao = "Processo de liquidação reiniciado pelo Operador.";
            }

            if(empty($observacao)) $observacao = "Avanço de fase.";
            $obs_formatada = "[{$timestamp} - {$perfil}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                $stmtCur->execute([$item_id]);
                $fase_anterior = $stmtCur->fetchColumn();

                $sql_up = "UPDATE de_itens SET status_atual = ?, observacao_atual = ?";
                $params_up = [$novo_status, $obs_formatada];
                
                if (!empty($update_fields)) {
                    $sql_up .= ", " . implode(", ", $update_fields);
                    $params_up = array_merge($params_up, $update_values);
                }
                $sql_up .= " WHERE id = ?";
                $params_up[] = $item_id;

                $stmtUp = $db->prepare($sql_up);
                $stmtUp->execute($params_up);

                $stmtEv = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtEv->execute([$item_id, $usuario, $perfil, $acao_log, $fase_anterior, $novo_status, $observacao]);

                $db->commit();
                header("Location: /operador/fila");
                exit();
            } catch (\Exception $e) {
                $db->rollBack(); die("Erro Tático: " . $e->getMessage());
            }
        }
    }
}