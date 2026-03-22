<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;
use Exception;

class DEController {
    
    public function create() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        require __DIR__ . '/../views/de_create.php';
    }

    public function store() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            
            $origem = $_SESSION['origem_setor'] ?? 'BAMRJ'; 
            $observacao = trim($_POST['observacao'] ?? 'Lançamento inicial do Lote.');
            
            $cpfs = $_POST['cpf_cnpj'] ?? [];
            $docs = $_POST['num_doc_fiscal'] ?? [];
            $valores = $_POST['valor_total'] ?? [];
            $pas = $_POST['pa_numero'] ?? [];
            $prioridades = $_POST['prioridade_flag'] ?? [];
            
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: DE Criada - \"{$observacao}\"";

            $hash = strtoupper(substr(uniqid(), -4));
            $numero_geral_de = "DE-" . date('Y') . "-" . $hash;

            try {
                $db->beginTransaction();

                $stmtLote = $db->prepare("INSERT INTO de_lotes (numero_geral, origem_tipo, criado_por) VALUES (?, ?, ?) RETURNING id");
                $stmtLote->execute([$numero_geral_de, $origem, $usuario]);
                $lote_id = $stmtLote->fetchColumn();

                for ($i = 0; $i < count($cpfs); $i++) {
                    $cpf_cnpj = preg_replace('/\D/', '', $cpfs[$i]);
                    $num_doc = trim($docs[$i]);
                    $valor_total = str_replace(['.', ','], ['', '.'], $valores[$i]);
                    $pa_numero = ($origem === 'OMAP' && !empty($pas[$i])) ? trim($pas[$i]) : null;
                    
                    $is_priority = (isset($prioridades[$i]) && $prioridades[$i] == '1') ? 1 : 0;

                    if (empty($num_doc) || empty($valor_total)) continue; 

                    $stmtItem = $db->prepare("INSERT INTO de_itens (lote_id, cpf_cnpj, num_documento_fiscal, valor_total, pa_numero, status_atual, observacao_atual, prioridade) VALUES (?, ?, ?, ?, ?, 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?, ?) RETURNING id");
                    $stmtItem->execute([$lote_id, $cpf_cnpj, $num_doc, $valor_total, $pa_numero, $obs_formatada, $is_priority]);
                    $item_id = $stmtItem->fetchColumn();

                    $stmtEvento = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'CRIAR_DE', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?)");
                    $stmtEvento->execute([$item_id, $usuario, $perfil, $observacao]);
                }

                $db->commit();
                header("Location: /index");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                die("<div style='background:#dc3545;color:white;padding:20px;'><h1>⚠️ Falha na Inserção</h1><p>" . $e->getMessage() . "</p></div>");
            }
        }
    }

    public function acompanhar() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        
        $id = $_GET['id'] ?? 0;
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM de_lotes WHERE id = ?");
        $stmt->execute([$id]);
        $lote = $stmt->fetch();
        if (!$lote) die("Lote não encontrado.");

        $stmtItens = $db->prepare("SELECT * FROM de_itens WHERE lote_id = ? ORDER BY id ASC");
        $stmtItens->execute([$id]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Auditoria
        $stmtEv = $db->prepare("SELECT e.*, i.num_documento_fiscal FROM de_eventos e JOIN de_itens i ON e.item_id = i.id WHERE i.lote_id = ? ORDER BY e.timestamp DESC");
        $stmtEv->execute([$id]);
        $auditoria = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/de_acompanhar.php';
    }

    public function reenviar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $lote_id = $_POST['lote_id'] ?? 0;
            $observacao = trim($_POST['observacao'] ?? 'Corrigido e reenviado.');
            
            $novo_doc = trim($_POST['num_doc'] ?? '');
            $novo_valor = str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
            
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');
            $obs_formatada = "[{$timestamp} - {$perfil}]: REENVIADO - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', observacao_atual = ?, num_documento_fiscal = ?, valor_total = ? WHERE id = ?");
                $stmt->execute([$obs_formatada, $novo_doc, $novo_valor, $item_id]);

                $stmtEv = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'REENVIAR_ORIGEM', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?)");
                $stmtEv->execute([$item_id, $usuario, $perfil, "Doc ajustado para $novo_doc / R$ $novo_valor. Obs: $observacao"]);

                $db->commit();
                header("Location: /de/acompanhar?id=" . $lote_id);
                exit();
            } catch (\Exception $e) { $db->rollBack(); die("Erro ao reenviar."); }
        }
    }

    public function excluirItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $lote_id = $_POST['lote_id'] ?? 0;
            $motivo = trim($_POST['motivo_cancelamento'] ?? 'Cancelamento solicitado.');
            
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: SOLICITAÇÃO DE CANCELAMENTO: \"{$motivo}\". Aguardando Aval da Execução Fin.";

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_AVAL_CANCELAMENTO', observacao_atual = ? WHERE id = ?");
                $stmt->execute([$obs_formatada, $item_id]);

                $stmtEv = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'SOLICITAR_CANCELAMENTO', 'AGUARDANDO_AVAL_CANCELAMENTO', ?)");
                $stmtEv->execute([$item_id, $usuario, $perfil, $motivo]);

                $db->commit();
                header("Location: /de/acompanhar?id=" . $lote_id);
                exit();
            } catch (\Exception $e) { $db->rollBack(); die("Erro."); }
        }
    }
}