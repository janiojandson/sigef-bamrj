<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class ProtocoloController {
    
    // 🗂️ 1. Mostra a Fila de LOTES (DE) que possuem itens pendentes
    public function fila() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin'])) { header("Location: /"); exit(); }
        
        $db = Database::getConnection();
        
        // Busca apenas as DEs que têm pelo menos 1 item aguardando protocolo
        $sql = "SELECT DISTINCT l.* FROM de_lotes l 
                JOIN de_itens i ON l.id = i.lote_id 
                WHERE i.status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' 
                ORDER BY l.criado_em ASC";
                
        $stmt = $db->query($sql);
        $lotes_pendentes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // Vamos usar a mesma view que faremos a seguir, ou uma específica
        require __DIR__ . '/../views/protocolo_fila.php';
    }

    // 📄 2. Abre a DE para ver os Itens
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

    // ✅ 3. Receber Item (Aprovar)
    public function receberItem() {
        $this->processarAcao('AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'RECEBER_PROTOCOLO');
    }

    // ❌ 4. Rejeitar Item (Devolver)
    public function rejeitarItem() {
        if (empty(trim($_POST['observacao'] ?? ''))) {
            die("<script>alert('Obrigatório preencher o motivo da rejeição!'); history.back();</script>");
        }
        $this->processarAcao('REJEITADO_PELO_PROTOCOLO', 'REJEITAR_PROTOCOLO');
    }

    // Motor Interno de Transição
    private function processarAcao($nova_fase, $acao_log) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $lote_id = $_POST['lote_id'] ?? 0;
            $observacao = trim($_POST['observacao'] ?? 'Processado pelo Protocolo.');
            
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');
            
            $obs_formatada = "[{$timestamp} - {$perfil}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();

                $stmt = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?");
                $stmt->execute([$nova_fase, $obs_formatada, $item_id]);

                $stmtEvento = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) 
                                            VALUES (?, ?, ?, ?, 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?, ?)");
                $stmtEvento->execute([$item_id, $usuario, $perfil, $acao_log, $nova_fase, $observacao]);

                $db->commit();
                header("Location: /protocolo/lote?id=" . $lote_id);
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                die("Erro: " . $e->getMessage());
            }
        }
    }
}