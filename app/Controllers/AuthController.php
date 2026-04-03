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
                    $_SESSION['origem_setor'] = $user['origem_setor'];
                    
                    // 🛡️ Captura se ele precisa mudar a senha
                    $_SESSION['must_change_password'] = $user['must_change_password']; 
                    
                    if ($_SESSION['must_change_password']) {
                        header("Location: /mudar_senha");
                    } else {
                        header("Location: /index");
                    }
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

    // 👇 NOVO MÉTODO 👇
    public function mudarSenha() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $senha_atual = $_POST['senha_atual'] ?? '';
            $nova_senha = $_POST['nova_senha'] ?? '';
            $confirma_senha = $_POST['confirma_senha'] ?? '';

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($senha_atual, $user['password_hash'])) {
                $error = "A senha atual está incorreta.";
            } elseif (strlen($nova_senha) < 6) {
                $error = "A nova senha deve ter no mínimo 6 caracteres por segurança.";
            } elseif ($nova_senha !== $confirma_senha) {
                $error = "A nova senha e a confirmação não coincidem.";
            } else {
                // Sucesso: Atualiza o banco e tira a trava de primeiro acesso
                $hash = password_hash($nova_senha, PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password_hash = ?, must_change_password = FALSE WHERE id = ?")
                   ->execute([$hash, $_SESSION['user_id']]);
                
                $_SESSION['must_change_password'] = false; // Destrava a sessão
                $success = "Senha alterada com sucesso! Redirecionando para o painel...";
                header("refresh:2;url=/"); // Redireciona após 2 segundos
            }
        }
        require __DIR__ . '/../views/mudar_senha.php';
    }
}
