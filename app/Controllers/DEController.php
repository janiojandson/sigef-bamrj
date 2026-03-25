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
            $observacao = trim($_POST['observacao'] ?? 'Lançamento inicial.');
            
            $cpfs = $_POST['cpf_cnpj'] ?? []; 
            $docs = $_POST['num_doc_fiscal'] ?? []; 
            $nss = $_POST['ns_numero'] ?? []; 
            $prioridades = $_POST['prioridade_flag'] ?? [];
            
            $usuario = $_SESSION['username']; 
            $perfil = $_SESSION['role'];
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: DE Criada - \"{$observacao}\"";
            $numero_geral_de = "DE-" . date('Y') . "-" . strtoupper(substr(uniqid(), -4));

            try {
                $db->beginTransaction();
                $stmtLote = $db->prepare("INSERT INTO de_lotes (numero_geral, origem_tipo, criado_por) VALUES (?, ?, ?) RETURNING id");
                $stmtLote->execute([$numero_geral_de, $origem, $usuario]); 
                $lote_id = $stmtLote->fetchColumn();

                for ($i = 0; $i < count($cpfs); $i++) {
                    $cpf_cnpj = preg_replace('/\D/', '', $cpfs[$i]); 
                    $num_doc = trim($docs[$i]); 
                    $ns_numero = (!empty($nss[$i])) ? trim($nss[$i]) : null;
                    $is_priority = (isset($prioridades[$i]) && $prioridades[$i] == '1') ? 1 : 0;
                    
                    if (empty($num_doc)) continue; 

                    // O valor entra fixo como 0.00 já que o sistema não gerencia mais finanças
                    $stmtItem = $db->prepare("INSERT INTO de_itens (lote_id, cpf_cnpj, num_documento_fiscal, valor_total, ns_numero, status_atual, observacao_atual, prioridade) VALUES (?, ?, ?, 0.00, ?, 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?, ?) RETURNING id");
                    $stmtItem->execute([$lote_id, $cpf_cnpj, $num_doc, $ns_numero, $obs_formatada, $is_priority]);
                    $item_id = $stmtItem->fetchColumn();
                    
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'CRIAR_DE', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?)")->execute([$item_id, $usuario, $perfil, $observacao]);
                }
                $db->commit();
                echo "<script>alert('DE Gerada com Sucesso! Encaminhe para o Protocolo.\\nNúmero: {$numero_geral_de}'); window.location.href='/';</script>"; 
                exit();
            } catch (Exception $e) { 
                $db->rollBack(); 
                die("<div style='padding:20px; background:#dc3545; color:white;'><h1>⚠️ Falha</h1><p>" . $e->getMessage() . "</p></div>"); 
            }
        }
    }

    public function acompanhar() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $id = $_GET['id'] ?? 0; $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM de_lotes WHERE id = ?"); 
        $stmt->execute([$id]); 
        $lote = $stmt->fetch();
        if (!$lote) die("Lote não encontrado.");

        $stmtItens = $db->prepare("SELECT * FROM de_itens WHERE lote_id = ? ORDER BY id ASC");
        $stmtItens->execute([$id]); 
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

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
            $novo_ns = trim($_POST['ns_numero'] ?? ''); 
            
            $usuario = $_SESSION['username']; 
            $perfil = $_SESSION['role']; 
            $timestamp = date('d/m/Y H:i');
            $obs_formatada = "[{$timestamp} - {$perfil}]: REENVIADO - \"{$observacao}\"";

            $justificativa_log = "Doc ajustado para $novo_doc";
            if (!empty($novo_ns)) $justificativa_log .= " / NS: $novo_ns";
            $justificativa_log .= ". Obs: $observacao";

            try {
                $db->beginTransaction();
                // O valor não é mais atualizado aqui, apenas os dados do documento e a nova NS
                $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', observacao_atual = ?, num_documento_fiscal = ?, ns_numero = ? WHERE id = ?")
                   ->execute([$obs_formatada, $novo_doc, empty($novo_ns) ? null : $novo_ns, $item_id]);
                
                $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'REENVIAR_ORIGEM', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?)")
                   ->execute([$item_id, $usuario, $perfil, $justificativa_log]);
                
                $db->commit(); 
                header("Location: /de/acompanhar?id=" . $lote_id); 
                exit();
            } catch (\Exception $e) { 
                $db->rollBack(); 
                die("Erro ao reenviar: " . $e->getMessage()); 
            }
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
            $obs_formatada = "[" . date('d/m/Y H:i') . " - {$perfil}]: SOLICITAÇÃO DE CANCELAMENTO: \"{$motivo}\". Aguardando Aval.";

            try {
                $db->beginTransaction();
                $db->prepare("UPDATE de_itens SET status_atual = 'AGUARDANDO_AVAL_CANCELAMENTO', observacao_atual = ? WHERE id = ?")->execute([$obs_formatada, $item_id]);
                $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'SOLICITAR_CANCELAMENTO', 'AGUARDANDO_AVAL_CANCELAMENTO', ?)")->execute([$item_id, $usuario, $perfil, $motivo]);
                $db->commit(); 
                header("Location: /de/acompanhar?id=" . $lote_id); 
                exit();
            } catch (\Exception $e) { 
                $db->rollBack(); 
                die("Erro."); 
            }
        }
    }
}