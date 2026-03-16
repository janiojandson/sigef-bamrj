<?php
require_once 'app/core/Database.php';
require_once 'app/models/User.php';

use App\Core\Database;
use App\Models\User;

// Simulação de variáveis de ambiente para execução via CLI local (opcional)
// No Railway, o Database.php já lerá as variáveis reais.

try {
    $db = Database::getConnection();
    
    $username = 'admin';
    $password = 'admin123'; // Senha padrão militar para o primeiro acesso
    $hash = User::hashPassword($password);

    $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, must_change_password) 
                          VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute([
        'Administrador', 
        $username, 
        $hash, 
        'Admin', 
        false // O Admin principal começa sem a trava por padrão
    ]);

    echo "Utilizador Admin criado com sucesso no Railway!\n";
} catch (Exception $e) {
    echo "Erro ao criar admin: " . $e->getMessage();
}