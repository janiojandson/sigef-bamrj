<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class AdminController {
    
    // 💣 ROTA SECRETA: Construtor do Banco de Dados
    public function resetDatabase() {
        $db = Database::getConnection();
        try {
            $db->exec("DROP TABLE IF EXISTS de_eventos CASCADE;");
            $db->exec("DROP TABLE IF EXISTS de_itens CASCADE;");
            $db->exec("DROP TABLE IF EXISTS de_lotes CASCADE;");
            $db->exec("DROP TABLE IF EXISTS users CASCADE;");          

            $db->exec("
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(128) NOT NULL,
                    username VARCHAR(64) UNIQUE NOT NULL,
                    password_hash VARCHAR(256) NOT NULL,
                    role VARCHAR(64) NOT NULL,
                    origem_setor VARCHAR(64) DEFAULT 'BAMRJ', -- 🛡️ NOVO CAMPO
                    must_change_password BOOLEAN DEFAULT TRUE
                );

                CREATE TABLE de_lotes (
                    id SERIAL PRIMARY KEY,
                    numero_geral VARCHAR(32) UNIQUE NOT NULL,
                    origem_tipo VARCHAR(20) NOT NULL,
                    status_lote VARCHAR(64) DEFAULT 'EM_ELABORACAO',
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    criado_por VARCHAR(64)
                );

                CREATE TABLE de_itens (
                    id SERIAL PRIMARY KEY,
                    lote_id INTEGER REFERENCES de_lotes(id) ON DELETE CASCADE,
                    cpf_cnpj VARCHAR(20) NOT NULL,
                    num_documento_fiscal VARCHAR(50) NOT NULL,
                    valor_total DECIMAL(15, 2) NOT NULL,
                    pa_numero VARCHAR(50),
                    np_numero VARCHAR(50),
                    lf_numero VARCHAR(50),
                    op_numero VARCHAR(50),
                    ob_numero VARCHAR(50),
                    data_pagamento DATE,
                    status_atual VARCHAR(64) DEFAULT 'EM_ELABORACAO',
                    prioridade BOOLEAN DEFAULT FALSE,
                    observacao_atual TEXT
                );

                CREATE TABLE de_eventos (
                    id SERIAL PRIMARY KEY,
                    item_id INTEGER REFERENCES de_itens(id) ON DELETE CASCADE,
                    usuario_nip VARCHAR(64) NOT NULL,
                    perfil_atuante VARCHAR(64) NOT NULL,
                    acao VARCHAR(128) NOT NULL,
                    fase_anterior VARCHAR(64),
                    fase_nova VARCHAR(64),
                    justificativa TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
            ");

            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            // Master Admin nasce como BAMRJ
            $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, origem_setor, must_change_password) VALUES (?, ?, ?, ?, ?, false)");
            $stmt->execute(['Administrador', 'admin', $hash, 'Admin', 'BAMRJ']);

            echo "<div style='background:#004488;color:white;padding:30px;font-family:sans-serif;text-align:center;'>
                    <h1>✅ OPERAÇÃO BEM-SUCEDIDA!</h1>
                    <p>Banco reestruturado com suporte à Origem Setorial.</p>
                    <a href='/login' style='background:#28a745;color:white;padding:15px;text-decoration:none;border-radius:5px;'>IR PARA O LOGIN</a>
                  </div>";
        } catch (\Exception $e) {
            echo "<h1>⚠️ Falha</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // 🛡️ TELA DE CADASTRO DE USUÁRIOS
    public function users() {
        if (($_SESSION['role'] ?? '') !== 'Admin') { header("Location: /"); exit(); }
        $db = Database::getConnection();

        // Se o formulário foi enviado (Criar novo usuário)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'Operador';
            
            // 🛡️ REGRA TÁTICA: OMAP vs BAMRJ
            if ($role === 'OMAP') {
                $sigla = strtoupper(trim($_POST['omap_sigla'] ?? ''));
                $origem = "OMAP - " . $sigla;
            } elseif ($role === 'Setor_BAMRJ') {
                $origem = 'BAMRJ';
            } else {
                $origem = 'BAMRJ'; // Default para os demais
            }

            // Verifica se utilizador já existe
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                die("<script>alert('Erro: Este NIP/Utilizador já existe.'); history.back();</script>");
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, origem_setor, must_change_password) VALUES (?, ?, ?, ?, ?, FALSE)");
            $stmt->execute([$name, $username, $hash, $role, $origem]);

            header("Location: /admin/users");
            exit();
        }

        // Busca todos os usuários para listar na tabela
        $stmt = $db->query("SELECT id, name, username, role, origem_setor FROM users ORDER BY name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin_users.php';
    }

    // 🛡️ EXCLUIR USUÁRIO
    public function deleteUser() {
        if (($_SESSION['role'] ?? '') !== 'Admin') { header("Location: /"); exit(); }
        $id = $_GET['id'] ?? 0;
        
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
        $stmt->execute([$id]);
        
        header("Location: /admin/users");
        exit();
    }
}