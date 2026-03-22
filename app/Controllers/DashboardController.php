<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class DashboardController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? 'Operador';
        
        // Busca os últimos 50 Lotes de Encaminhamento (DE)
        $stmt = $db->query("SELECT * FROM de_lotes ORDER BY criado_em DESC LIMIT 50");
        $lotes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // Contagem Rápida para os "Cards" do painel
        $stmtCount = $db->query("SELECT status_lote, COUNT(*) as total FROM de_lotes GROUP BY status_lote");
        $contagens = $stmtCount ? $stmtCount->fetchAll(PDO::FETCH_KEY_PAIR) : [];
        
        $total_elaboracao = $contagens['EM_ELABORACAO'] ?? 0;
        
        require __DIR__ . '/../views/dashboard.php';
    }
}