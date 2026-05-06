<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class DashboardController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? 'Operador';
        $origem = $_SESSION['origem_setor'] ?? 'BAMRJ';
        $username = $_SESSION['username'];
        $q = trim($_GET['q'] ?? '');
        $ano = $_GET['ano'] ?? date('Y');

        // 🔄 Toggle do substituto via GET (mantém compatibilidade com o dashboard antigo)
        if (isset($_GET['substituto'])) {
            $novo_estado = ($_GET['substituto'] === '1');
            $db->prepare("UPDATE users SET substituto_ativo = ? WHERE id = ?")
               ->execute([$novo_estado ? TRUE : FALSE, $_SESSION['user_id']]);
            $_SESSION['atuando_substituto'] = $novo_estado;
            header("Location: /"); exit();
        }
        
        // 🔄 Sincroniza o estado do substituto com o BD (fonte de verdade)
        $stmt_sub = $db->prepare("SELECT substituto_ativo FROM users WHERE id = ?");
        $stmt_sub->execute([$_SESSION['user_id']]);
        $atuando_substituto = (bool)($stmt_sub->fetchColumn() ?? false);
        $_SESSION['atuando_substituto'] = $atuando_substituto;

        $lotes = [];

        if ($role === 'Admin') { require __DIR__ . '/../views/dashboard.php'; return; }

        if (!empty($q)) {
            $is_search = true;
            
            if (str_starts_with($q, '#')) {
                $id_busca = (int) str_replace('#', '', $q);
                $stmt = $db->prepare("SELECT DISTINCT l.*, i.status_atual as status_inbox, i.ob_arquivo, i.ob_numero FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.id = ?");
                $stmt->execute([$id_busca]);
                $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                require __DIR__ . '/../views/dashboard.php'; return;
            }
            
            $termo = "%{$q}%";
            $sqlBusca = "SELECT DISTINCT l.*, i.status_atual as status_inbox FROM de_lotes l LEFT JOIN de_itens i ON l.id = i.lote_id WHERE (l.numero_geral ILIKE ? OR i.cpf_cnpj ILIKE ? OR i.num_documento_fiscal ILIKE ?) AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em DESC LIMIT 100";
            $stmt = $db->prepare($sqlBusca); $stmt->execute([$termo, $termo, $termo, $ano]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            require __DIR__ . '/../views/dashboard.php'; return;
        }

        if (in_array($role, ['OMAP', 'Setor_BAMRJ'])) {
            $sql = "SELECT DISTINCT l.*, (SELECT COUNT(*) FROM de_itens i2 WHERE i2.lote_id = l.id AND i2.status_atual LIKE '%REJEITAD%') as qtd_rejeitados 
                    FROM de_lotes l 
                    WHERE (l.origem_tipo = ? OR l.criado_por = ?) 
                    AND l.id IN (SELECT lote_id FROM de_itens WHERE status_atual NOT IN ('ARQUIVADO', 'CANCELADO_PELA_ORIGEM'))
                    AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute([$origem, $username, $ano]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        elseif ($role === 'Operador') {
            $stmt = $db->prepare("SELECT DISTINCT l.* FROM de_lotes l 
                JOIN de_itens i ON l.id = i.lote_id 
                WHERE i.status_atual NOT IN ('ARQUIVADO', 'CANCELADO_PELA_ORIGEM') 
                AND EXTRACT(YEAR FROM l.criado_em) = ?
                ORDER BY l.criado_em DESC LIMIT 100");
            $stmt->execute([$ano]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif ($role === 'Protocolo') {
            $stmt = $db->prepare("SELECT DISTINCT l.* FROM de_lotes l 
                JOIN de_itens i ON l.id = i.lote_id 
                WHERE i.status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' 
                AND EXTRACT(YEAR FROM l.criado_em) = ?
                ORDER BY l.criado_em ASC");
            $stmt->execute([$ano]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif (in_array($role, ['Gestor_Financeiro', 'Gestor_Substituto', 'Chefe_Departamento', 'Agente_Fiscal', 'Ordenador_Despesas'])) {
            $fases_map = [
                'Gestor_Financeiro' => $atuando_substituto ? ['AGU_ASS_GESTOR_FINANCEIRO', 'AGU_VRF_CHEINTE'] : ['AGU_ASS_GESTOR_FINANCEIRO'],
                'Gestor_Substituto' => $atuando_substituto ? ['AGU_ASS_GESTOR_FINANCEIRO', 'AGU_VRF_CHEINTE'] : ['AGU_ASS_GESTOR_FINANCEIRO'],
                'Chefe_Departamento' => $atuando_substituto ? ['AGU_VRF_CHEINTE', 'AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'],
                'Agente_Fiscal' => $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR', 'AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'],
                'Ordenador_Despesas' => ['AGU_ASS_DIRETOR'],
            ];
            $fases_inbox = $fases_map[$role] ?? [];
            
            if (!empty($fases_inbox)) {
                $in = str_repeat('?,', count($fases_inbox) - 1) . '?';
                $sql = "SELECT DISTINCT l.* FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual IN ($in) AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em ASC";
                $params = array_merge($fases_inbox, [$ano]);
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        require __DIR__ . '/../views/dashboard.php';
    }

    public function checkInbox() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['count' => 0]); exit(); }
        
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? '';
        
        // 🔄 Sincroniza substituto com BD
        $stmt_sub = $db->prepare("SELECT substituto_ativo FROM users WHERE id = ?");
        $stmt_sub->execute([$_SESSION['user_id']]);
        $atuando_substituto = (bool)($stmt_sub->fetchColumn() ?? false);
        $_SESSION['atuando_substituto'] = $atuando_substituto;
        
        $count = 0;
        
        if ($role === 'Protocolo') {
            $stmt = $db->query("SELECT COUNT(DISTINCT lote_id) FROM de_itens WHERE status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO'");
            $count = (int)$stmt->fetchColumn();
        } elseif ($role === 'Operador') {
            $fases = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB', 'AGUARDANDO_AVAL_CANCELAMENTO', 'REJEITADO_PELO_ASSINADOR'];
            $in = str_repeat('?,', count($fases) - 1) . '?';
            $stmt = $db->prepare("SELECT COUNT(*) FROM de_itens WHERE status_atual IN ($in)");
            $stmt->execute($fases);
            $count = (int)$stmt->fetchColumn();
        } elseif (in_array($role, ['Gestor_Financeiro', 'Gestor_Substituto', 'Chefe_Departamento', 'Agente_Fiscal', 'Ordenador_Despesas'])) {
            $fases_map = [
                'Gestor_Financeiro' => $atuando_substituto ? ['AGU_ASS_GESTOR_FINANCEIRO', 'AGU_VRF_CHEINTE'] : ['AGU_ASS_GESTOR_FINANCEIRO'],
                'Gestor_Substituto' => $atuando_substituto ? ['AGU_ASS_GESTOR_FINANCEIRO', 'AGU_VRF_CHEINTE'] : ['AGU_ASS_GESTOR_FINANCEIRO'],
                'Chefe_Departamento' => $atuando_substituto ? ['AGU_VRF_CHEINTE', 'AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'],
                'Agente_Fiscal' => $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR', 'AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'],
                'Ordenador_Despesas' => ['AGU_ASS_DIRETOR'],
            ];
            $fases = $fases_map[$role] ?? [];
            if (!empty($fases)) {
                $in = str_repeat('?,', count($fases) - 1) . '?';
                $stmt = $db->prepare("SELECT COUNT(*) FROM de_itens WHERE status_atual IN ($in)");
                $stmt->execute($fases);
                $count = (int)$stmt->fetchColumn();
            }
        }
        
        echo json_encode(['count' => $count]);
        exit();
    }
}