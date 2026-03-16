<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class AdminController {
    
    // 🛡️ Trava de Segurança Reutilizável
    private function checkAdminAccess() {
        if (($_SESSION['role'] ?? '') !== 'Admin') {
            http_response_code(403);
            die("Acesso Negado: Privilégios insuficientes no perímetro do Assinador-BAMRJ.");
        }
    }

    public function createUser() {
        $this->checkAdminAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'Operador';

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) die("<h1>Erro Crítico</h1><p>Este utilizador já existe.</p>");

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, TRUE)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$name, $username, $hash, $role]);

            header("Location: /index");
            exit();
        }
    }

    public function editUser() {
        $this->checkAdminAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'] ?? 0;
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? ''; 

            $db = Database::getConnection();
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET role = ?, password_hash = ?, must_change_password = TRUE WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$role, $hash, $user_id]);
            } else {
                $sql = "UPDATE users SET role = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$role, $user_id]);
            }
            header("Location: /index");
            exit();
        }
    }

    public function deleteUser($id) {
        $this->checkAdminAccess();
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user && $user['username'] !== 'admin') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
        }
        header("Location: /index");
        exit();
    }

    // 💣 ROTA SECRETA: Construtor do Banco de Dados
    public function resetDatabase() {
        $db = Database::getConnection();
        try {
            // 1. Destruição Tática (Limpa tudo para evitar conflitos)
            $db->exec("DROP TABLE IF EXISTS document_files CASCADE;");
            $db->exec("DROP TABLE IF EXISTS events CASCADE;");
            $db->exec("DROP TABLE IF EXISTS documents CASCADE;");
            $db->exec("DROP TABLE IF EXISTS users CASCADE;");

            // 2. Reconstrução Estrutural
            $db->exec("
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(128) NOT NULL,
                    username VARCHAR(64) UNIQUE NOT NULL,
                    password_hash VARCHAR(256) NOT NULL,
                    role VARCHAR(64) NOT NULL,
                    must_change_password BOOLEAN DEFAULT TRUE
                );

                CREATE TABLE documents (
                    id SERIAL PRIMARY KEY,
                    protocol VARCHAR(32) UNIQUE NOT NULL,
                    name VARCHAR(128) NOT NULL,
                    cpf_cnpj VARCHAR(20),
                    solemp VARCHAR(50),
                    status VARCHAR(64) DEFAULT 'Caixa de Entrada - Enc. Finanças',
                    is_priority BOOLEAN DEFAULT FALSE,
                    current_observation TEXT,
                    uploader_name VARCHAR(64),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE events (
                    id SERIAL PRIMARY KEY,
                    document_id INTEGER REFERENCES documents(id) ON DELETE CASCADE,
                    user_name VARCHAR(64),
                    action VARCHAR(64) NOT NULL,
                    observation TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE document_files (
                    id SERIAL PRIMARY KEY,
                    document_id INTEGER REFERENCES documents(id) ON DELETE CASCADE,
                    filename VARCHAR(256) NOT NULL,
                    file_type VARCHAR(64) NOT NULL
                );
            ");

            // 3. Criação do Master Admin
            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, false)");
            $stmt->execute(['Administrador', 'admin', $hash, 'Admin']);

            echo "<div style='background:#28a745;color:white;padding:20px;font-family:sans-serif;'>
                    <h1>✅ Senhor! Base de dados (Postgres) recriada com sucesso!</h1>
                    <p>As tabelas foram construídas. A trava de segurança foi inserida. O senhor já pode acessar o sistema normal com <b>admin</b> e <b>admin123</b>.</p>
                    <a href='/login' style='color:white;text-decoration:underline;font-weight:bold;'>Clique aqui para acessar</a>
                  </div>";
        } catch (\Exception $e) {
            echo "<div style='background:#dc3545;color:white;padding:20px;font-family:sans-serif;'>
                    <h1>⚠️ Falha na Criação Estrutural</h1>
                    <p>" . htmlspecialchars($e->getMessage()) . "</p>
                  </div>";
        }
    }
}