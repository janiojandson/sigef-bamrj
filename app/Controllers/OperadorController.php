<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class OperadorController {
    
    public function fila() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); }
        $db = Database::getConnection();
        
        $fases = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB'];
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

    // 🚀 LÓGICA DE AGRUPAMENTO DE RAP
    public function gerarRapLote() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $itens = $_POST['itens_selecionados'] ?? [];
            if (empty($itens)) die("<script>alert('Selecione pelo menos uma nota para gerar o RAP!'); history.back();</script>");

            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');
            
            // Gera um número único de RAP (Ex: RAP-2026-A1B2)
            $hash = strtoupper(substr(uniqid(), -4));
            $numero_rap = "RAP-" . date('Y') . "-" . $hash;

            try {
                $db->beginTransaction();

                // 1. Cria o Lote RAP no banco
                $stmtRap = $db->prepare("INSERT INTO de_raps (numero_rap, criado_por) VALUES (?, ?) RETURNING id");
                $stmtRap->execute([$numero_rap, $usuario]);
                $rap_id = $stmtRap->fetchColumn();

                $novo_status = 'AGU_ASS_GESTOR_FINANCEIRO';
                $acao_log = 'GERAR_RAP';
                $observacao = "Agrupado no Lote: " . $numero_rap . " e remetido ao Gestor Financeiro.";

                // 2. Associa todos os itens selecionados a este RAP e avança o status
                foreach ($itens as $item_id) {
                    $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                    $stmtCur->execute([$item_id]);
                    $fase_anterior = $stmtCur->fetchColumn();

                    $stmtUp = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ?, rap_id = ? WHERE id = ?");
                    $stmtUp->execute([$novo_status, "[$timestamp - $perfil]: $acao_log - \"$observacao\"", $rap_id, $item_id]);

                    $stmtEv = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmtEv->execute([$item_id, $usuario, $perfil, $acao_log, $fase_anterior, $novo_status, $observacao]);
                }

                $db->commit();
                echo "<script>alert('Lote $numero_rap gerado com sucesso!'); window.location.href='/operador/fila';</script>";
                exit();
            } catch (\Exception $e) {
                $db->rollBack(); die("Erro Tático: " . $e->getMessage());
            }
        }
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
            } elseif ($tipo_acao === 'inserir_ob') {
                $novo_status = 'ARQUIVADO'; $acao_log = 'INSERIR_OB_ARQUIVAR';
                $update_fields[] = 'ob_numero = ?'; $update_values[] = strtoupper(trim($_POST['valor_input']));
                $update_fields[] = 'data_pagamento = ?'; $update_values[] = $_POST['data_pagamento'];
                
                // 📂 UPLOAD DA OB (PDF)
                if (isset($_FILES['ob_arquivo']) && $_FILES['ob_arquivo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../public/uploads/ob/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $fileName = time() . '_' . basename($_FILES['ob_arquivo']['name']);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['ob_arquivo']['tmp_name'], $filePath)) {
                        $update_fields[] = 'ob_arquivo = ?';
                        $update_values[] = '/uploads/ob/' . $fileName;
                    }
                }
                $observacao = "Processo liquidado e arquivado.";
            } elseif ($tipo_acao === 'rejeitar') {
                $novo_status = 'REJEITADO_EXEC_FIN'; $acao_log = 'REJEITAR_EXEC_FIN';
                if(empty($observacao)) die("<script>alert('A justificativa de rejeição é obrigatória!'); history.back();</script>");
            } elseif ($tipo_acao === 'reiniciar') { 
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
                if (!empty($update_fields)) { $sql_up .= ", " . implode(", ", $update_fields); $params_up = array_merge($params_up, $update_values); }
                $sql_up .= " WHERE id = ?"; $params_up[] = $item_id;

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