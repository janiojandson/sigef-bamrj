<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class AuthController {
    public function login() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: /index");
                    exit();
                } else {
                    $error = "Credenciais inválidas.";
                }
            } catch (\Exception $e) {
                $error = "Erro no banco de dados. Execute a rota secreta primeiro.";
            }
        }
        require __DIR__ . '/../views/login.php';
    }
}