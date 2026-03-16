<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;
use Exception;

class ArchiveController {
    
    // 🛡️ Tática de Infiltração: Cria uma sessão sem senha para Consulta Pública
    public static function simulatePublicAccess() {
        session_unset();
        session_destroy();
        session_start();
        
        $_SESSION['user_id'] = 0; // Usuário Fantasma
        $_SESSION['username'] = 'consulta_publica';
        $_SESSION['name'] = 'Consulta Pública';
        $_SESSION['role'] = 'Usuário Comum';
        $_SESSION['must_change_password'] = false;
        
        header("Location: /arquivo");
        exit();
    }

    // 🗄️ Motor de Busca do Arquivo Geral
    public function getArchiveData(): array {
        $data = [
            'role' => $_SESSION['role'] ?? '',
            'search_query' => $_GET['q'] ?? '',
            'documents' => []
        ];

        try {
            $db = Database::getConnection();
        } catch (Exception $e) {
            return $data; // Retorna vazio se falhar a Base de Dados
        }

        $search_query = trim($_GET['q'] ?? '');
        $search_query_clean = preg_replace('/\D/', '', $search_query);
        $ano_filtro = $_GET['ano'] ?? date('Y');

        $data['search_query'] = $search_query;

        // Condição Base: Apenas processos finalizados
        $sql = "SELECT * FROM documents 
                WHERE status IN ('Arquivado', 'Cancelado', 'Anulado', 'Reforçado') 
                AND EXTRACT(YEAR FROM created_at) = ?";
        $params = [$ano_filtro];

        // Se o usuário digitou algo na busca
        if (!empty($search_query)) {
            $sql .= " AND (name ILIKE ? OR protocol ILIKE ? OR cpf_cnpj ILIKE ? OR solemp ILIKE ?)";
            $like_q = "%{$search_query}%";
            $like_clean = "%{$search_query_clean}%";
            array_push($params, $like_q, $like_q, $like_clean, $like_clean);
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $all_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 🛡️ Regra de Negócio: Público não vê a lista geral se não pesquisar nada
        if ($data['role'] === 'Usuário Comum' && empty($search_query)) {
            $data['documents'] = [];
        } else {
            $data['documents'] = is_array($all_docs) ? $all_docs : [];
        }

        return $data;
    }
}