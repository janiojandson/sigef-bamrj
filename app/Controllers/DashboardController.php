<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;
use Exception;

class DashboardController {
    
    // 🛡️ Tipagem estrita: Este método NUNCA poderá retornar null
    public function getDashboardData(): array {
        
        // 1. Inicializa o pacote de dados padrão para evitar retornos vazios
        $data = [
            'role' => $_SESSION['role'] ?? 'Operador',
            'is_substitute' => $_SESSION['is_substitute'] ?? false,
            'users' => [],
            'documents' => [],
            'pre_protocol' => '',
            'inbox_count' => 0
        ];

        // 2. Proteção contra falhas no Banco de Dados
        try {
            $db = Database::getConnection();
        } catch (Exception $e) {
            return $data; 
        }
        
        // Regra: Usuário Comum vai direto para o Arquivo
        if ($data['role'] === 'Usuário Comum') {
            header("Location: /arquivo");
            exit();
        }

        $search_query = $_GET['q'] ?? '';
        $search_query_clean = preg_replace('/\D/', '', $search_query);
        $ano_filtro = $_GET['ano'] ?? date('Y');

        // 3. VISÃO DO ADMIN
        if ($data['role'] === 'Admin') {
            try {
                // Buscamos apenas o necessário, evitando expor o hash de senhas
                $stmt = $db->query("SELECT id, name, username, role FROM users ORDER BY name ASC");
                if ($stmt) {
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $data['users'] = is_array($users) ? $users : [];
                }
            } catch (Exception $e) {
                $data['users'] = [];
            }
            return $data; // Retorno garantido do Admin
        }

        // 4. MODO DE PESQUISA ATIVO
        if (!empty($search_query)) {
            $sql = "SELECT * FROM documents 
                    WHERE EXTRACT(YEAR FROM created_at) = ? 
                    AND (name ILIKE ? OR protocol ILIKE ? OR cpf_cnpj ILIKE ? OR solemp ILIKE ?)
                    ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $like_q = "%{$search_query}%";
            $like_clean = "%{$search_query_clean}%";
            $stmt->execute([$ano_filtro, $like_q, $like_q, $like_clean, $like_clean]);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['documents'] = is_array($docs) ? $docs : [];
            return $data;
        }

        // 5. VISÃO DO OPERADOR
        if ($data['role'] === 'Operador') {
            $sql = "SELECT * FROM documents 
                    WHERE status NOT IN ('Arquivado', 'Cancelado', 'Anulado', 'Reforçado') 
                    ORDER BY is_priority DESC, created_at DESC";
            $stmt = $db->query($sql);
            $docs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $data['documents'] = is_array($docs) ? $docs : [];
            
            $date_str = date('Ymd');
            $uuid = strtoupper(substr(uniqid(), 0, 4));
            $data['pre_protocol'] = "BAMRJ-{$date_str}-{$uuid}";
            
            $inbox_count = 0;
            foreach($data['documents'] as $d) {
                if (in_array($d['status'], ['Devolvido - Operador', 'Aguardando Empenho - Operador'])) {
                    $inbox_count++;
                }
            }
            $data['inbox_count'] = $inbox_count;
            return $data;
        }

        // 6. VISÃO DAS CHEFIAS (Workflow de Aprovação)
        $inbox_statuses = [];
        if ($data['role'] === 'Enc_Financas' || $data['role'] === 'Ajudante_Encarregado') {
            $inbox_statuses[] = 'Caixa de Entrada - Enc. Finanças';
        } elseif ($data['role'] === 'Chefe_Departamento') {
            $inbox_statuses[] = 'Caixa de Entrada - Chefe';
            if ($data['is_substitute']) $inbox_statuses[] = 'Caixa de Entrada - Vice-Diretor';
        } elseif ($data['role'] === 'Vice_Diretor') {
            $inbox_statuses[] = 'Caixa de Entrada - Vice-Diretor';
            if ($data['is_substitute']) $inbox_statuses[] = 'Caixa de Entrada - Diretor';
        } elseif ($data['role'] === 'Diretor') {
            $inbox_statuses[] = 'Caixa de Entrada - Diretor';
        }

        if (!empty($inbox_statuses)) {
            $placeholders = implode(',', array_fill(0, count($inbox_statuses), '?'));
            $sql = "SELECT * FROM documents WHERE status IN ($placeholders) ORDER BY is_priority DESC, created_at ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($inbox_statuses);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data['documents'] = is_array($docs) ? $docs : [];
            $data['inbox_count'] = count($data['documents']);
        }

        // 7. Retorno final (Garantia de saída para qualquer outro cenário)
        return $data; 
    }
}