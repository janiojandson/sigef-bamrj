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

        $lotes = [];

        if ($role === 'Admin') {
            require __DIR__ . '/../views/dashboard.php';
            return;
        }

        // 🔍 LÓGICA DE BUSCA
        if (!empty($q)) {
            $termo = "%{$q}%";
            $sqlBusca = "SELECT DISTINCT l.* FROM de_lotes l 
                         LEFT JOIN de_itens i ON l.id = i.lote_id 
                         WHERE l.numero_geral ILIKE ? OR i.cpf_cnpj ILIKE ? OR i.num_documento_fiscal ILIKE ?
                         ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sqlBusca);
            $stmt->execute([$termo, $termo, $termo]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            require __DIR__ . '/../views/dashboard.php';
            return;
        }

        // 📥 LÓGICA DE CAIXA DE ENTRADA
        $fases_inbox = [];

        if (in_array($role, ['OMAP', 'Setor_BAMRJ'])) {
            $sql = "SELECT DISTINCT l.*, 
                    (SELECT COUNT(*) FROM de_itens i2 WHERE i2.lote_id = l.id AND i2.status_atual LIKE '%REJEITAD%') as qtd_rejeitados
                    FROM de_lotes l 
                    WHERE l.origem_tipo = ? OR l.criado_por = ?
                    ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute([$origem, $username]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        elseif ($role === 'Operador') {
            // Operador vê lotes que têm itens rejeitados por ele OU itens atualmente em liquidação
            $sql = "SELECT DISTINCT l.* FROM de_lotes l 
                    JOIN de_itens i ON l.id = i.lote_id 
                    WHERE i.status_atual IN ('AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB', 'REJEITADO_EXEC_FIN')
                    ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->query($sql);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            if ($role === 'Protocolo') $fases_inbox[] = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO';
            elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') $fases_inbox[] = 'AGU_ASS_GESTOR_FINANCEIRO'; // 🛡️ NOVO NOME
            elseif ($role === 'Chefe_Departamento') $fases_inbox[] = 'AGU_VRF_CHEINTE'; // 🛡️ NOVO NOME
            elseif ($role === 'Vice_Diretor') $fases_inbox[] = 'AGU_VRF_VICE_DIRETOR'; // 🛡️ NOVO NOME
            elseif ($role === 'Diretor') $fases_inbox[] = 'AGU_ASS_DIRETOR'; // 🛡️ NOVO NOME

            if (!empty($fases_inbox)) {
                $in = str_repeat('?,', count($fases_inbox) - 1) . '?';
                $sql = "SELECT DISTINCT l.* FROM de_lotes l JOIN de_itens i ON l.id = i.lote_id WHERE i.status_atual IN ($in) ORDER BY l.criado_em ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute($fases_inbox);
                $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        require __DIR__ . '/../views/dashboard.php';
    }

    public function getInboxCount() {
        if (!isset($_SESSION['user_id'])) return 0;
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? 'Operador';
        $origem = $_SESSION['origem_setor'] ?? 'BAMRJ';
        
        if ($role === 'Admin') return 0;

        $fases = [];
        if ($role === 'Protocolo') $fases[] = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO';
        elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') $fases[] = 'AGU_ASS_GESTOR_FINANCEIRO';
        elseif ($role === 'Chefe_Departamento') $fases[] = 'AGU_VRF_CHEINTE';
        elseif ($role === 'Vice_Diretor') $fases[] = 'AGU_VRF_VICE_DIRETOR';
        elseif ($role === 'Diretor') $fases[] = 'AGU_ASS_DIRETOR';

        if (!empty($fases)) {
            $in = str_repeat('?,', count($fases) - 1) . '?';
            $stmt = $db->prepare("SELECT COUNT(DISTINCT lote_id) FROM de_itens WHERE status_atual IN ($in)");
            $stmt->execute($fases);
            return (int) $stmt->fetchColumn();
        }

        if ($role === 'Operador') {
            $fases_op = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB'];
            $in = str_repeat('?,', count($fases_op) - 1) . '?';
            $stmt = $db->prepare("SELECT COUNT(DISTINCT lote_id) FROM de_itens WHERE status_atual IN ($in)");
            $stmt->execute($fases_op);
            return (int) $stmt->fetchColumn();
        }

        if (in_array($role, ['OMAP', 'Setor_BAMRJ'])) {
            $stmt = $db->prepare("SELECT COUNT(DISTINCT i.lote_id) FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE l.origem_tipo = ? AND i.status_atual LIKE '%REJEITAD%'");
            $stmt->execute([$origem]);
            return (int) $stmt->fetchColumn();
        }

        return 0;
    }
}