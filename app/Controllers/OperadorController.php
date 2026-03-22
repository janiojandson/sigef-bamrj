<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class OperadorController {
    
    public function fila() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); }
        $db = Database::getConnection();
        
        // 🛡️ Escuta exatamente a fase que o Protocolo envia
        $fases = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_INSERCAO_OP'];
        $in = str_repeat('?,', count($fases) - 1) . '?';
        
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual IN ($in) ORDER BY i.prioridade DESC, l.criado_em ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($fases);
        $todos_itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $itens_receber = []; $itens_np = []; $itens_lf = [];
        foreach ($todos_itens as $item) {
            if ($item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_EXEC_FIN') $itens_receber[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_NP') $itens_np[] = $item;
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_LF') $itens_lf[] = $item;
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

            $novo_status = ''; $acao_log = ''; $campo_update = null; $valor_update = null;

            if ($tipo_acao === 'receber') {
                $novo_status = 'AGUARDANDO_INSERCAO_NP'; $acao_log = 'RECEBIMENTO_EXEC_FIN';
                if(empty($observacao)) $observacao = 'Recebido na Execução Financeira.';
            } elseif ($tipo_acao === 'inserir_np') {
                $novo_status = 'AGUARDANDO_INSERCAO_LF'; $acao_log = 'INSERCAO_NP';
                $campo_update = 'np_numero'; $valor_update = strtoupper(trim($_POST['np_numero']));
                if(empty($valor_update)) die("<script>alert('Número da NP é obrigatório!'); history.back();</script>");
                $observacao = "NP Registrada: $valor_update. " . $observacao;
            } elseif ($tipo_acao === 'inserir_lf') {
                $novo_status = 'AGUARDANDO_INSERCAO_OP'; $acao_log = 'INSERCAO_LF';
                $campo_update = 'lf_numero'; $valor_update = strtoupper(trim($_POST['lf_numero']));
                if(empty($valor_update)) die("<script>alert('Número da LF é obrigatório!'); history.back();</script>");
                $observacao = "LF Registrada: $valor_update. " . $observacao;
            } elseif ($tipo_acao === 'rejeitar') {
                $novo_status = 'REJEITADO_EXEC_FIN'; // 🛡️ OMAP enxergará isso também
                $acao_log = 'REJEICAO_EXEC_FIN';
                if(empty($observacao)) die("<script>alert('Justificativa OBRIGATÓRIA!'); history.back();</script>");
            }

            $obs_formatada = "[{$timestamp} - {$perfil}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                $stmtCur->execute([$item_id]);
                $fase_anterior = $stmtCur->fetchColumn();

                if ($campo_update) {
                    $stmtUp = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ?, $campo_update = ? WHERE id = ?");
                    $stmtUp->execute([$novo_status, $obs_formatada, $valor_update, $item_id]);
                } else {
                    $stmtUp = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?");
                    $stmtUp->execute([$novo_status, $obs_formatada, $item_id]);
                }

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