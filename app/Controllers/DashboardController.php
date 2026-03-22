<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class DashboardController {
    
    // 📊 CONSTRUTOR DO PAINEL PRINCIPAL
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? 'Operador';
        $origem = $_SESSION['origem_setor'] ?? 'BAMRJ';
        $username = $_SESSION['username'];
        $q = trim($_GET['q'] ?? '');

        $lotes = [];
        $total_elaboracao = 0;

        // 1. CONTAGEM RÁPIDA (DEs em Elaboração)
        $stmtCount = $db->query("SELECT COUNT(*) FROM de_lotes WHERE status_lote = 'EM_ELABORACAO'");
        $total_elaboracao = $stmtCount ? $stmtCount->fetchColumn() : 0;

        // 🛡️ Se for Admin, bloqueia o carregamento de processos e renderiza a tela limpa
        if ($role === 'Admin') {
            require __DIR__ . '/../views/dashboard.php';
            return;
        }

        // 🔍 2. LÓGICA DE BUSCA GLOBAL (Acionada se o usuário usar a barra de pesquisa)
        if (!empty($q)) {
            $termo = "%{$q}%";
            // Busca lotes pelo número da DE ou por dados dos Itens (CPF/CNPJ ou NF)
            $sqlBusca = "SELECT DISTINCT l.* FROM de_lotes l 
                         LEFT JOIN de_itens i ON l.id = i.lote_id 
                         WHERE l.numero_geral ILIKE ? 
                         OR i.cpf_cnpj ILIKE ? 
                         OR i.num_documento_fiscal ILIKE ?
                         ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sqlBusca);
            $stmt->execute([$termo, $termo, $termo]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            require __DIR__ . '/../views/dashboard.php';
            return;
        }

        // 📥 3. LÓGICA DE CAIXA DE ENTRADA (Filtro por Perfil da Máquina de Estados)
        $fases_inbox = [];

        // Regra OMAP: Vê o que ela mesma originou
        if ($role === 'OMAP') {
            $sql = "SELECT DISTINCT l.* FROM de_lotes l 
                    WHERE l.origem_tipo = ? OR l.criado_por = ?
                    ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute([$origem, $username]);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
        // Regra Operador: Vê todos os lotes que possuem itens ativos (Não arquivados)
        elseif ($role === 'Operador') {
            $sql = "SELECT DISTINCT l.* FROM de_lotes l 
                    JOIN de_itens i ON l.id = i.lote_id 
                    WHERE i.status_atual != 'ARQUIVADO'
                    ORDER BY l.criado_em DESC LIMIT 50";
            $stmt = $db->query($sql);
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // Regra Protocolo e Assinadores: Vão receber apenas lotes com Itens nas suas respectivas fases
        else {
            if ($role === 'Protocolo') {
                $fases_inbox[] = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO';
            }
            elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') {
                $fases_inbox[] = 'AGUARDANDO_ASSINATURA_NIVEL_1';
            }
            elseif ($role === 'Chefe_Departamento') {
                $fases_inbox[] = 'AGUARDANDO_ASSINATURA_NIVEL_2';
            }
            elseif ($role === 'Vice_Diretor') {
                $fases_inbox[] = 'AGUARDANDO_ASSINATURA_NIVEL_3';
            }
            elseif ($role === 'Diretor') {
                $fases_inbox[] = 'AGUARDANDO_ASSINATURA_DIRETOR';
            }

            // Executa a busca baseada nas fases interceptadas
            if (!empty($fases_inbox)) {
                $in = str_repeat('?,', count($fases_inbox) - 1) . '?';
                $sql = "SELECT DISTINCT l.* FROM de_lotes l 
                        JOIN de_itens i ON l.id = i.lote_id 
                        WHERE i.status_atual IN ($in)
                        ORDER BY l.criado_em ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute($fases_inbox);
                $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        require __DIR__ . '/../views/dashboard.php';
    }

    // 📡 ROTA DO RADAR: Computa notificações em tempo real sem recarregar a tela
    public function getInboxCount() {
        if (!isset($_SESSION['user_id'])) return 0;
        
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? 'Operador';
        $origem = $_SESSION['origem_setor'] ?? 'BAMRJ';
        
        if ($role === 'Admin') return 0;

        $fases = [];
        if ($role === 'Protocolo') $fases[] = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO';
        elseif ($role === 'Enc_Financas' || $role === 'Ajudante_Encarregado') $fases[] = 'AGUARDANDO_ASSINATURA_NIVEL_1';
        elseif ($role === 'Chefe_Departamento') $fases[] = 'AGUARDANDO_ASSINATURA_NIVEL_2';
        elseif ($role === 'Vice_Diretor') $fases[] = 'AGUARDANDO_ASSINATURA_NIVEL_3';
        elseif ($role === 'Diretor') $fases[] = 'AGUARDANDO_ASSINATURA_DIRETOR';

        // 1. Contagem para Protocolo e Assinadores
        if (!empty($fases)) {
            $in = str_repeat('?,', count($fases) - 1) . '?';
            $stmt = $db->prepare("SELECT COUNT(DISTINCT lote_id) FROM de_itens WHERE status_atual IN ($in)");
            $stmt->execute($fases);
            return (int) $stmt->fetchColumn();
        }

        // 2. Contagem para Operador (Sinaliza se há trabalho urgente)
        if ($role === 'Operador') {
            $fases_op = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_UPLOAD_OB'];
            $in = str_repeat('?,', count($fases_op) - 1) . '?';
            $stmt = $db->prepare("SELECT COUNT(DISTINCT lote_id) FROM de_itens WHERE status_atual IN ($in)");
            $stmt->execute($fases_op);
            return (int) $stmt->fetchColumn();
        }

        // 3. Contagem para OMAP (Alerta Apenas se houver Rejeição)
        if ($role === 'OMAP') {
            $stmt = $db->prepare("SELECT COUNT(DISTINCT i.lote_id) FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE l.origem_tipo = ? AND i.status_atual LIKE '%REJEITAD%'");
            $stmt->execute([$origem]);
            return (int) $stmt->fetchColumn();
        }

        return 0;
    }
}