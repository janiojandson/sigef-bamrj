<?php
namespace App\Controllers;

use App\Models\User;

class AuthController {
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = User::authenticate($username, $password);

            if ($user) {
                // Monta a Sessão Tática
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['unit_omap'] = $user['unit_omap'];
                $_SESSION['must_change_password'] = $user['must_change_password'];

                if ($user['must_change_password']) {
                    header("Location: /setup_password");
                } else {
                    header("Location: /dashboard");
                }
                exit();
            }
            return "Credenciais de acesso inválidas.";
        }
        return null;
    }

    // Função que estava faltando para trocar a senha!
    public function setupPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($newPassword) || empty($confirmPassword)) {
                return "As senhas não podem estar vazias.";
            }

            if ($newPassword !== $confirmPassword) {
                return "As senhas digitadas não coincidem.";
            }

            if (strlen($newPassword) < 6) {
                return "A senha deve ter no mínimo 6 caracteres.";
            }

            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                // Chama a função do Model que criptografa e salva
                $updated = User::updatePassword($userId, $newPassword);
                
                if ($updated) {
                    // Atualiza a sessão destravando o usuário
                    $_SESSION['must_change_password'] = false;
                    header("Location: /dashboard");
                    exit();
                } else {
                    return "Erro na base de dados ao salvar a nova senha.";
                }
            } else {
                return "Sessão inválida. Por favor, faça login novamente.";
            }
        }
        return null;
    }

    public function logout() {
        session_destroy();
        header("Location: /login");
        exit();
    }
}