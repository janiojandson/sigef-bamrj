<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class ProtocoloController {
    
    public function fila() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin', 'Operador'])) { header("Location: /"); exit(); }
        $db = Database::getConnection();
        $sql = "SELECT DISTINCT l.* FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' ORDER BY l.criado_em ASC";
        $stmt = $db->query($sql);
        $lotes_pendentes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        require __DIR__ . '/../views/protocolo_fila.php';
    }

    public function verLote() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin', 'Operador'])) { header("Location: /"); exit(); }
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

    /**
     * 🖨️ Imprimir Capa de Protocolo — A4 Retrato
     * Cabeçalho com dados do documento + Tabela com linhas em branco
     * Primeira linha automática: Protocolo, Data do envio, espaço para rubrica e assunto
     */
    public function imprimirCapa() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin', 'Operador'])) { header("Location: /"); exit(); }
        
        $item_id = $_GET['item_id'] ?? 0;
        $db = Database::getConnection();
        
        $stmtItem = $db->prepare("SELECT * FROM de_itens WHERE id = ?");
        $stmtItem->execute([$item_id]);
        $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
        if (!$item) die("Item não encontrado.");

        $lote_id = $item['lote_id'];
        $stmt = $db->prepare("SELECT * FROM de_lotes WHERE id = ?");
        $stmt->execute([$lote_id]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lote) die("Lote não encontrado.");
        
        $itens = [$item];
        
        require __DIR__ . '/../views/protocolo_imprimir_capa.php';
    }

    public function receberItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $itens = $_POST['itens_selecionados'] ?? [];
            $lote_id = $_POST['lote_id'] ?? 0;

            if (empty($itens)) {
                die("<script>alert('Selecione pelo menos um documento para receber.'); history.back();</script>");
            }

            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: RECEBER_PROTOCOLO - \"Documento físico recebido na Base.\"";

            try {
                $db->beginTransaction();
                foreach ($itens as $item_id) {
                    $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN', observacao_atual = ? WHERE id = ?")->execute([$obs_formatada, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'RECEBER_PROTOCOLO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', 'AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'Entrada física na Base')")->execute([$item_id, $usuario, $perfil]);
                }
                
                // Registra a data de envio ao protocolo
                $db->prepare("UPDATE de_lotes SET data_envio_protocolo = NOW() WHERE id = ? AND data_envio_protocolo IS NULL")
                   ->execute([$lote_id]);
                
                $db->commit();
                header("Location: /protocolo/lote?id=" . $lote_id);
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                die("Erro ao receber: " . $e->getMessage());
            }
        }
    }

    public function devolverItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $motivo = trim($_POST['motivo_devolucao'] ?? '');
            $lote_id = $_POST['lote_id'] ?? 0;

            if (empty($motivo)) {
                die("<script>alert('Informe o motivo da devolução.'); history.back();</script>");
            }

            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: DEVOLVIDO_PELO_PROTOCOLO - \"{$motivo}\"";

            try {
                $db->prepare("UPDATE de_itens SET status_atual = 'REJEITADO_PELO_ASSINADOR', observacao_atual = ? WHERE id = ?")
                   ->execute([$obs_formatada, $item_id]);
                $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'DEVOLVER_PROTOCOLO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', 'REJEITADO_PELO_ASSINADOR', ?)")
                   ->execute([$item_id, $usuario, $perfil, $motivo]);
                
                header("Location: /protocolo/lote?id=" . $lote_id);
                exit();
            } catch (\Exception $e) {
                die("Erro ao devolver: " . $e->getMessage());
            }
        }
    }

    /**
     * ❌ REJEITAR FÍSICO — Novo fluxo de rejeição pelo Protocolo
     * O documento é devolvido IMEDIATAMENTE à caixa de entrada da unidade de origem (OMAP/BAMRJ)
     * com o status REJEITADO_FISICO_PROTOCOLO, ficando desbloqueado para correção e reenvio.
     */
    public function rejeitarItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            // [ANTES] $motivo = trim($_POST['observacao'] ?? '');
            // [DEPOIS] Usa o novo campo motivo_rejeicao_fisica do modal
            $motivo = trim($_POST['motivo_rejeicao_fisica'] ?? '');
            $lote_id = $_POST['lote_id'] ?? 0;

            if (empty($motivo)) {
                die("<script>alert('O motivo da rejeição física é OBRIGATÓRIO!'); history.back();</script>");
            }

            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');

            // [ANTES] Status genérico 'REJEITADO_PELO_ASSINADOR' que não distinguia rejeição física
            // [DEPOIS] Status específico 'REJEITADO_FISICO_PROTOCOLO' para rastreabilidade e alerta visual
            $nova_fase = 'REJEITADO_FISICO_PROTOCOLO';
            $obs_formatada = "[{$timestamp} - {$perfil}]: REJEITADO_FISICO_PROTOCOLO - \"{$motivo}\" — Documento devolvido à unidade de origem para correção.";

            try {
                $db->beginTransaction();

                // Busca a fase atual para auditoria
                $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                $stmtCur->execute([$item_id]);
                $fase_anterior = $stmtCur->fetchColumn();

                // Atualiza o status para REJEITADO_FISICO_PROTOCOLO — documento regressa à origem
                $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                   ->execute([$nova_fase, $obs_formatada, $item_id]);

                // Registra evento de auditoria completo
                $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'REJEITAR_FISICO_PROTOCOLO', ?, ?, ?)")
                   ->execute([$item_id, $usuario, $perfil, $fase_anterior, $nova_fase, $motivo]);

                $db->commit();
                header("Location: /protocolo/lote?id=" . $lote_id);
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                die("Erro ao rejeitar fisicamente: " . $e->getMessage());
            }
        }
    }
}