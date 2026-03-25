<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class RelatorioController {
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        
        $db = Database::getConnection();
        $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Início do mês atual
        $data_fim = $_GET['data_fim'] ?? date('Y-m-t'); // Fim do mês atual

        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo 
                FROM de_itens i 
                JOIN de_lotes l ON i.lote_id = l.id 
                WHERE i.status_atual = 'ARQUIVADO' 
                AND i.data_pagamento BETWEEN ? AND ? 
                ORDER BY i.data_pagamento DESC, i.id DESC";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([$data_inicio, $data_fim]);
        $relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/relatorio_ob.php';
    }
}