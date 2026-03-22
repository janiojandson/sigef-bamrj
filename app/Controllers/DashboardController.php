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

        if (isset($_GET['substituto'])) {
            $_SESSION['atuando_substituto'] = ($_GET['substituto'] === '1');
            header("Location: /"); exit();
        }
        $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;

        $lotes = [];
        if ($role === 'Admin') { require __DIR__ . '/../views/dashboard.php'; return; }

        if (!empty($q)) {
            $termo = "%{$q}%";
            $sqlBusca = "SELECT DISTINCT l.*, i.status_atual as status_inbox FROM de_lotes l LEFT JOIN de_itens i ON l.id = i.lote_id WHERE (l.numero_geral ILIKE ? OR i.cpf_cnpj ILIKE ? OR i.num_documento_fiscal ILIKE ?) ORDER BY l.criado_em DESC LIMIT 100";
            $stmt = $db->prepare($sqlBusca); $stmt->execute([$termo, $termo, $termo]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $is_search = true;
            require __DIR__ . '/../views/dashboard.php'; return;
        }

        $fases_inbox = [];
        if (in_array($role, ['OMAP', 'Setor_BAMRJ'])) {
            $stmt = $db->prepare("SELECT DISTINCT l.*, (SELECT COUNT(*) FROM de_itens i2 WHERE i2.lote_id = l.id AND i2.status_atual LIKE '%REJEITAD%') as qtd_rejeitados FROM de_lotes l WHERE (l.origem_tipo = ? OR l.criado_por = ?) AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em DESC LIMIT 50");
            $stmt->execute([$origem, $username, $ano]); $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        elseif ($role === 'Operador') {
            $stmt = $db->prepare("SELECT DISTINCT l.* FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual NOT IN ('EM_ELABORACAO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', 'ARQUIVADO', 'CANCELADO_PELA_ORIGEM') AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em DESC LIMIT 50");
            $stmt->execute([$ano]); $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            if ($role === 'Protocolo') $fases_inbox = ['AGUARDANDO_RECEBIMENTO_PROTOCOLO'];
            elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') $fases_inbox = ['AGU_ASS_GESTOR_FINANCEIRO']; 
            elseif ($role === 'Chefe_Departamento') $fases_inbox = $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'];
            elseif ($role === 'Vice_Diretor') $fases_inbox = $atuando_substituto ? ['AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'];
            elseif ($role === 'Diretor') $fases_inbox = ['AGU_ASS_DIRETOR']; 

            if (!empty($fases_inbox)) {
                $in = str_repeat('?,', count($fases_inbox) - 1) . '?';
                $params = array_merge($fases_inbox, [$ano]);
                $stmt = $db->prepare("SELECT DISTINCT l.*, i.status_atual as status_inbox FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual IN ($in) AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em ASC");
                $stmt->execute($params); $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        require __DIR__ . '/../views/dashboard.php';
    }
    public function getInboxCount() { return 0; }
}