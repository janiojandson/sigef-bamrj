<?php
namespace App\Controllers;

use App\core\Database;
use PDO;

class AdminController {
    
    public function createUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $unit_omap = $_POST['unit_omap'] ?? null;

            if (empty($unit_omap)) {
                $unit_omap = null;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);

            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, unit_omap, must_change_password) VALUES (?, ?, ?, ?, ?, true)");
                $stmt->execute([$name, $username, $hash, $role, $unit_omap]);
                
                header("Location: /dashboard?success=user_created");
                exit();
            } catch (\Exception $e) {
                die("Erro ao criar utilizador: O NIP/Login já existe ou houve uma falha na base de dados.");
            }
        }
    }
}