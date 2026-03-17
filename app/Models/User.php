<?php
namespace App\Models;

use App\core\Database; // <-- Ajustado para minúsculo (core)
use PDO;

class User {
    public static function authenticate($username, $password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    public static function updatePassword($id, $newPassword) {
        $db = Database::getConnection();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, must_change_password = FALSE WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }
}