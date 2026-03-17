<?php
namespace App\Controllers;

use App\core\Database;
use PDO;
use Exception;

class ProtocoloController {
    
    public function painel() {
        if ($_SESSION['role'] !== 'Protocolo') die("Acesso negado.");

        $db = Database::getConnection();
        
        // O Protocolo só enxerga o que acabou de chegar da OMAP/Setor Interno
        $stmt = $db->query("SELECT d.*, u.name as criador_nome, u.unit_omap 
                            FROM documentos_encaminhamento d 
                            JOIN users u ON d.criado_por = u.id 
                            WHERE d.status_geral = 'ENVIADO_PROTOCOLO'
                            ORDER BY d.criado_em ASC");
        $des = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/protocolo_painel.php';
    }

    public function encaminhar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'Protocolo') {
            $de_id = $_POST['de_id'] ?? null;
            if (!$de_id) die("ID Inválido.");

            try {
                $db = Database::getConnection();
                $db->beginTransaction();

                // 1. Atualiza a Capa (Lote)
                $stmtDE = $db->prepare("UPDATE documentos_encaminhamento SET status_geral = 'EM_ANALISE_OPERADOR' WHERE id = ?");
                $stmtDE->execute([$de_id]);

                // 2. Atualiza os Itens (Somente os que estavam pendentes no protocolo)
                $stmtItem = $db->prepare("UPDATE itens_de SET status_item = 'EM_ANALISE_OPERADOR' WHERE de_id = ? AND status_item = 'ENVIADO_PROTOCOLO'");
                $stmtItem->execute([$de_id]);

                // 3. Auditoria
                $stmtAudit = $db->prepare("INSERT INTO auditoria (de_id, usuario_nome, perfil, acao, justificativa) VALUES (?, ?, ?, 'RECEBIDO_PROTOCOLO', 'Lote recebido fisicamente/virtualmente e encaminhado para Execução Financeira')");
                $stmtAudit->execute([$de_id, $_SESSION['name'], $_SESSION['role']]);

                $db->commit();
                header("Location: /protocolo/painel?sucesso=encaminhado");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                die("Erro no protocolo: " . $e->getMessage());
            }
        }
    }
}