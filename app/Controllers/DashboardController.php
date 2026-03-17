<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;
use Exception;

class DashboardController {
    
    public function getDashboardData(): array {
        $data = [
            'role' => $_SESSION['role'] ?? '',
            'name' => $_SESSION['name'] ?? '',
            'users' => [],
            'capas_de' => [],
            'raps' => []
        ];

        try {
            $db = Database::getConnection();
        } catch (Exception $e) {
            return $data; 
        }

        // 1. VISÃO DO ADMINISTRADOR
        if ($data['role'] === 'Admin') {
            $stmt = $db->query("SELECT id, name, username, role, unit_omap FROM users ORDER BY name ASC");
            $data['users'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return $data;
        }

        // 2. VISÃO DA OMAP (Unidade Externa)
        if ($data['role'] === 'OMAP' || $data['role'] === 'SETOR_INTERNO') {
            $stmt = $db->prepare("SELECT * FROM documentos_encaminhamento WHERE criado_por = ? ORDER BY criado_em DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $data['capas_de'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        }

        // 3. VISÃO DO OPERADOR (Centro da Execução Financeira)
        if ($data['role'] === 'Operador') {
            // Operador vê todas as Capas que não estão finalizadas
            $stmt = $db->query("SELECT * FROM documentos_encaminhamento ORDER BY criado_em DESC");
            $data['capas_de'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            
            // E vê também as RAPs criadas
            $stmtRap = $db->query("SELECT * FROM rap ORDER BY criado_em DESC");
            $data['raps'] = $stmtRap ? $stmtRap->fetchAll(PDO::FETCH_ASSOC) : [];
            return $data;
        }

        // 4. VISÃO DAS CHEFIAS (Visualizam apenas as RAPs - Malotes)
        $inbox_status = '';
        if ($data['role'] === 'Enc_Financas') $inbox_status = 'CAIXA_ENC_FINANCAS';
        elseif ($data['role'] === 'Chefe_Departamento') $inbox_status = 'CAIXA_CHEFE_DEP';
        elseif ($data['role'] === 'Vice_Diretor') $inbox_status = 'CAIXA_VICE_DIRETOR';
        elseif ($data['role'] === 'Diretor') $inbox_status = 'CAIXA_DIRETOR';

        if ($inbox_status !== '') {
            $stmt = $db->prepare("SELECT * FROM rap WHERE status_assinatura = ? ORDER BY criado_em ASC");
            $stmt->execute([$inbox_status]);
            $data['raps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $data;
    }
}