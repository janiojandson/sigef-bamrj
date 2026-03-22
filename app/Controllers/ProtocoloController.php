<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class ProtocoloController {
    public function fila() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin'])) { header("Location: /"); exit(); }
        $db = Database::getConnection();
        $sql = "SELECT DISTINCT l.* FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' ORDER BY l.criado_em ASC";
        $stmt = $db->query($sql);
        $lotes_pendentes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        require __DIR__ . '/../views/protocolo_fila.php';
    }

    public function verLote() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin'])) { header("Location: /"); exit(); }
        $id = $_GET['id'] ?? 0;
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM de_lotes WHERE id = ?");
        $stmt->execute([$id]);
        $lote = $stmt->fetch();
        if (!$lote) die("Lote não encontrado.");
        $stmtItens = $db->prepare("SELECT * FROM de_itens WHERE lote_id = ? ORDER BY prioridade DESC, id ASC");
        $stmtItens->execute([$id]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/protocolo_ver_lote.php';
    }

    public function receberItem() {
        // 🛡️ Envia para a fase exata que o Operador está escutando
        $this->processarAcao('AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'RECEBER_PROTOCOLO');
    }

    public function rejeitarItem() {
        if (empty(trim($_POST['observacao'] ?? ''))) die("<script>alert('Motivo obrigatório!'); history.back();</script>");
        // 🛡️ OMAP vai enxergar este status
        $this->processarAcao('REJEITADO_PELO_PROTOCOLO', 'REJEITAR_PROTOCOLO');
    }

    private function processarAcao($nova_fase, $acao_log) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $lote_id = $_POST['lote_id'] ?? 0;
            $observacao = trim($_POST['observacao'] ?? 'Processado.');
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?");
                $stmt->execute([$nova_fase, $obs_formatada, $item_id]);

                $stmtEv = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?, ?)");
                $stmtEv->execute([$item_id, $usuario, $perfil, $acao_log, $nova_fase, $observacao]);

                $db->commit();
                header("Location: /protocolo/lote?id=" . $lote_id);
                exit();
            } catch (\Exception $e) {
                $db->rollBack(); die("Erro: " . $e->getMessage());
            }
        }
    }
}