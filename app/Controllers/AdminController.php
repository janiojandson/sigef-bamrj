<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class AdminController {
    
    // 🛡️ Trava de Segurança Reutilizável
    private function checkAdminAccess() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'Admin') {
            header("Location: /"); 
            exit();
        }
    }

    // 🎯 Gerenciador Central da Rota /admin/users
    public function users() {
        $this->checkAdminAccess();
        $db = Database::getConnection();

        // 🛡️ Lida com as requisições de POST (Ações Rápidas do Painel)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            // ➕ Criar Usuário — SEM restrições de senha
            if ($action === 'create') {
                $name = $_POST['name'] ?? ''; 
                $username = $_POST['username'] ?? ''; 
                $password = $_POST['password'] ?? ''; 
                $role = $_POST['role'] ?? 'Operador';
                
                $origem = ($role === 'OMAP') ? "OMAP - " . strtoupper(trim($_POST['omap_sigla'] ?? '')) : 'BAMRJ';
                
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?"); 
                $stmt->execute([$username]);
                
                if ($stmt->fetch()) {
                    die("<script>alert('Erro: Utilizador já existe no sistema.'); history.back();</script>");
                }
                
                if (empty($password)) {
                    die("<script>alert('A senha não pode estar vazia.'); history.back();</script>");
                }
                
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (name, username, password_hash, role, origem_setor, must_change_password, substituto_ativo) VALUES (?, ?, ?, ?, ?, TRUE, FALSE)")
                   ->execute([$name, $username, $hash, $role, $origem]);
                   
                header("Location: /admin/users"); 
                exit();
            }

            // ✏️ Editar Usuário — SEM restrições de senha
            if ($action === 'edit') {
                $user_id = $_POST['user_id']; 
                $role = $_POST['role']; 
                $password = trim($_POST['password'] ?? '');
                
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET role = ?, password_hash = ?, must_change_password = TRUE WHERE id = ?")->execute([$role, $hash, $user_id]);
                } else {
                    $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $user_id]);
                }
                
                header("Location: /admin/users"); 
                exit();
            }

            // 🔄 PATCH SQL via Web (A Mágica da Migração sem Terminal)
            if ($action === 'migrate_db') {
                try {
                    $db->beginTransaction();

                    // 🟢 Cria colunas que podem faltar em ambientes antigos
                    $migracoes = [
                        "ALTER TABLE de_itens ADD COLUMN IF NOT EXISTS empresa_nome VARCHAR(255) DEFAULT 'Não Informado';",
                        "ALTER TABLE users ADD COLUMN IF NOT EXISTS origem_setor VARCHAR(128) DEFAULT 'BAMRJ';",
                        "ALTER TABLE users ADD COLUMN IF NOT EXISTS substituto_ativo BOOLEAN DEFAULT FALSE;",
                        "ALTER TABLE de_lotes ADD COLUMN IF NOT EXISTS data_envio_protocolo TIMESTAMP;",
                    ];
                    
                    foreach ($migracoes as $sql_mig) {
                        $db->exec($sql_mig);
                    }
                    
                    $db->commit();
                    die("<script>alert('✅ Migrações aplicadas com sucesso!'); location.href='/admin/users';</script>");
                } catch (\Exception $e) {
                    $db->rollBack();
                    die("<script>alert('❌ Erro na migração: " . addslashes($e->getMessage()) . "'); history.back();</script>");
                }
            }
        }

        // 📋 Listagem de Usuários
        $stmt = $db->query("SELECT * FROM users ORDER BY name ASC");
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        require __DIR__ . '/../views/admin_users.php';
    }
}