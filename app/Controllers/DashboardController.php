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

        // 🛡️ TOGGLE DE SUBSTITUTO (Salva na Sessão)
        if (isset($_GET['substituto'])) {
            $_SESSION['atuando_substituto'] = ($_GET['substituto'] === '1');
            header("Location: /"); exit();
        }
        $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;

        $lotes = [];

        if ($role === 'Admin') { require __DIR__ . '/../views/dashboard.php'; return; }

        // 🔍 LÓGICA DE BUSCA GLOBAL E AUDITORIA (Todos veem o resultado global se buscarem)
        if (!empty($q)) {
            $termo = "%{$q}%";
            $sqlBusca = "SELECT DISTINCT l.*, i.status_atual as status_inbox 
                         FROM de_lotes l 
                         LEFT JOIN de_itens i ON l.id = i.lote_id 
                         WHERE (l.numero_geral ILIKE ? OR i.cpf_cnpj ILIKE ? OR i.num_documento_fiscal ILIKE ?)
                         ORDER BY l.criado_em DESC LIMIT 100";
            $stmt = $db->prepare($sqlBusca);
            $stmt->execute([$termo, $termo, $termo]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $is_search = true; // Flag para a View
            require __DIR__ . '/../views/dashboard.php';
            return;
        }

        $fases_inbox = [];

        if (in_array($role, ['OMAP', 'Setor_BAMRJ'])) {
            $sql = "SELECT DISTINCT l.*, (SELECT COUNT(*) FROM de_itens i2 WHERE i2.lote_id = l.id AND i2.status_atual LIKE '%REJEITAD%') as qtd_rejeitados FROM de_lotes l WHERE (l.origem_tipo = ? OR l.criado_por = ?) AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute([$origem, $username, $ano]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        elseif ($role === 'Operador') {
            $sql = "SELECT DISTINCT l.* FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual NOT IN ('EM_ELABORACAO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', 'ARQUIVADO', 'CANCELADO_PELA_ORIGEM') AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute([$ano]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            if ($role === 'Protocolo') $fases_inbox = ['AGUARDANDO_RECEBIMENTO_PROTOCOLO'];
            elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') $fases_inbox = ['AGU_ASS_GESTOR_FINANCEIRO']; 
            
            // 🛡️ LÓGICA: Se Substituto ON, pega a fila do superior. Se OFF, pega a fila própria.
            elseif ($role === 'Chefe_Departamento') $fases_inbox = $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'];
            elseif ($role === 'Vice_Diretor') $fases_inbox = $atuando_substituto ? ['AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'];
            elseif ($role === 'Diretor') $fases_inbox = ['AGU_ASS_DIRETOR']; 

            if (!empty($fases_inbox)) {
                $in = str_repeat('?,', count($fases_inbox) - 1) . '?';
                $params = array_merge($fases_inbox, [$ano]);
                $sql = "SELECT DISTINCT l.*, i.status_atual as status_inbox FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual IN ($in) AND EXTRACT(YEAR FROM l.criado_em) = ? ORDER BY l.criado_em ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        require __DIR__ . '/../views/dashboard.php';
    }

    // 🛡️ CORREÇÃO: Função atualizada para contar os itens reais e alimentar o Radar da View
    public function getInboxCount() {
        if (!isset($_SESSION['user_id'])) return 0;
        
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? '';
        $username = $_SESSION['username'] ?? '';
        $origem = $_SESSION['origem_setor'] ?? '';
        $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;

        $count = 0;

        if (in_array($role, ['OMAP', 'Setor_BAMRJ'])) {
            // Conta quantos lotes têm itens rejeitados para a origem
            $stmt = $db->prepare("SELECT COUNT(DISTINCT l.id) FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE (l.origem_tipo = ? OR l.criado_por = ?) AND i.status_atual LIKE '%REJEITAD%'");
            $stmt->execute([$origem, $username]);
            $count = $stmt->fetchColumn();
        } elseif ($role === 'Operador') {
            // Conta itens na fila de execução
            $fases = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB', 'AGUARDANDO_AVAL_CANCELAMENTO', 'REJEITADO_PELO_ASSINADOR'];
            $in = str_repeat('?,', count($fases) - 1) . '?';
            $stmt = $db->prepare("SELECT COUNT(*) FROM de_itens WHERE status_atual IN ($in)");
            $stmt->execute($fases);
            $count = $stmt->fetchColumn();
        } else {
            // Conta para Assinadores e Protocolo
            $fases_inbox = [];
            if ($role === 'Protocolo') $fases_inbox = ['AGUARDANDO_RECEBIMENTO_PROTOCOLO'];
            elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') $fases_inbox = ['AGU_ASS_GESTOR_FINANCEIRO']; 
            elseif ($role === 'Chefe_Departamento') $fases_inbox = $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'];
            elseif ($role === 'Vice_Diretor') $fases_inbox = $atuando_substituto ? ['AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'];
            elseif ($role === 'Diretor') $fases_inbox = ['AGU_ASS_DIRETOR']; 

            if (!empty($fases_inbox)) {
                $in = str_repeat('?,', count($fases_inbox) - 1) . '?';
                $stmt = $db->prepare("SELECT COUNT(*) FROM de_itens WHERE status_atual IN ($in)");
                $stmt->execute($fases_inbox);
                $count = $stmt->fetchColumn();
            }
        }
        
        return $count;
    }
}