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

    public function logout() {
        session_destroy();
        header("Location: /login");
        exit();
    }
}