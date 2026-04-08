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

            // ➕ Criar Usuário
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
                
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (name, username, password_hash, role, origem_setor, must_change_password) VALUES (?, ?, ?, ?, ?, TRUE)")
                   ->execute([$name, $username, $hash, $role, $origem]);
                   
                header("Location: /admin/users"); 
                exit();
            }

            // ✏️ Editar Usuário
            if ($action === 'edit') {
                $user_id = $_POST['user_id']; 
                $role = $_POST['role']; 
                $password = trim($_POST['password'] ?? '');
                
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET role = ?, password_hash = ? WHERE id = ?")->execute([$role, $hash, $user_id]);
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

                    // 🟢 NOVA LINHA: Cria a coluna da Empresa nas notas existentes
                    $db->exec("ALTER TABLE de_itens ADD COLUMN IF NOT EXISTS empresa_nome VARCHAR(255) DEFAULT 'Não Informado';");
                    
                    // 1. Renomeia PA para NS
                    $db->exec("ALTER TABLE de_itens RENAME COLUMN pa_numero TO ns_numero;");
                    
                    // 2. Converte os perfis antigos para os novos nas tabelas de usuários
                    $db->exec("UPDATE users SET role = 'Gestor_Financeiro' WHERE role = 'Enc_Financas';");
                    $db->exec("UPDATE users SET role = 'Gestor_Substituto' WHERE role = 'Ajudante_Encarregado';");
                    $db->exec("UPDATE users SET role = 'Agente_Fiscal' WHERE role = 'Vice_Diretor';");
                    $db->exec("UPDATE users SET role = 'Ordenador_Despesas' WHERE role = 'Diretor';");

                    // 3. Atualiza os históricos de eventos do SIGEF para não quebrar a auditoria
                    $db->exec("UPDATE de_eventos SET perfil_atuante = 'Gestor_Financeiro' WHERE perfil_atuante = 'Enc_Financas';");

                    $db->commit();
                    echo "<script>alert('✅ Patch SQL Aplicado! Colunas e Nomenclaturas de Perfis Atualizadas.'); window.location.href='/admin/users';</script>"; 
                    exit();
                } catch (\Exception $e) {
                    $db->rollBack();
                    die("<div style='padding:20px; background:#dc3545; color:white; font-family:sans-serif; text-align:center;'>
                            <h1>⚠️ Aviso SQL</h1>
                            <p>Parece que o Patch já foi aplicado anteriormente ou a coluna 'pa_numero' não existe mais.</p>
                            <p><b>Detalhe do Erro:</b> " . htmlspecialchars($e->getMessage()) . "</p>
                            <a href='/admin/users' style='color:white; font-weight:bold; text-decoration:underline;'>Voltar ao Painel</a>
                         </div>");
                }
            }

            // 🧹 Wipe Data (Apaga apenas documentos e processos)
            if ($action === 'wipe_data') {
                try {
                    $db->exec("TRUNCATE TABLE de_eventos, de_itens, de_lotes, de_raps RESTART IDENTITY CASCADE;");
                    echo "<script>alert('🧹 Processos, DEs e RAPs limpos com sucesso. Utilizadores mantidos.'); window.location.href='/admin/users';</script>"; 
                    exit();
                } catch (\Exception $e) { 
                    die("Erro ao limpar dados: " . $e->getMessage()); 
                }
            }

            // ☢️ Factory Reset (Formatação Total da Estrutura)
            if ($action === 'factory_reset') {
                try {
                    // Apaga tabelas na ordem certa para não dar conflito de Chave Estrangeira
                    $db->exec("DROP TABLE IF EXISTS de_eventos CASCADE; 
                               DROP TABLE IF EXISTS de_itens CASCADE; 
                               DROP TABLE IF EXISTS de_lotes CASCADE; 
                               DROP TABLE IF EXISTS de_raps CASCADE; 
                               DROP TABLE IF EXISTS users CASCADE;");          
                    
                    // Recriação Limpa (Já com NS_Numero e a nova lógica de Arquivos)
                    $db->exec("
                        CREATE TABLE users (
                            id SERIAL PRIMARY KEY, 
                            name VARCHAR(128) NOT NULL, 
                            username VARCHAR(64) UNIQUE NOT NULL, 
                            password_hash VARCHAR(256) NOT NULL, 
                            role VARCHAR(64) NOT NULL, 
                            origem_setor VARCHAR(64) DEFAULT 'BAMRJ', 
                            must_change_password BOOLEAN DEFAULT FALSE
                        );

                        CREATE TABLE de_lotes (
                            id SERIAL PRIMARY KEY, 
                            numero_geral VARCHAR(32) UNIQUE NOT NULL, 
                            origem_tipo VARCHAR(20) NOT NULL, 
                            status_lote VARCHAR(64) DEFAULT 'EM_ELABORACAO', 
                            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
                            criado_por VARCHAR(64)
                        );

                        CREATE TABLE de_raps (
                            id SERIAL PRIMARY KEY, 
                            numero_rap VARCHAR(64) UNIQUE NOT NULL, 
                            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
                            criado_por VARCHAR(64)
                        );

                        CREATE TABLE de_itens (
                            id SERIAL PRIMARY KEY, 
                            lote_id INTEGER REFERENCES de_lotes(id) ON DELETE CASCADE, 
                            rap_id INTEGER REFERENCES de_raps(id) ON DELETE SET NULL, 
                            cpf_cnpj VARCHAR(20) NOT NULL,
                            empresa_nome VARCHAR(255) DEFAULT 'Não Informado', /* 🟢 NOVA LINHA AQUI */
                            num_documento_fiscal VARCHAR(50) NOT NULL, 
                            valor_total DECIMAL(15, 2) NOT NULL, 
                            ns_numero VARCHAR(50), 
                            np_numero VARCHAR(50), 
                            lf_numero VARCHAR(50), 
                            op_numero VARCHAR(50), 
                            ob_numero VARCHAR(50), 
                            ob_arquivo VARCHAR(255), 
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
                    
                    // Injeta o Super Administrador
                    $hash = password_hash('admin123', PASSWORD_BCRYPT);
                    $db->prepare("INSERT INTO users (name, username, password_hash, role, origem_setor) VALUES (?, ?, ?, ?, ?)")
                       ->execute(['Administrador', 'admin', $hash, 'Admin', 'BAMRJ']);
                    
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                    
                    echo "<div style='background:#28a745;color:white;padding:30px;font-family:sans-serif;text-align:center;margin-top:50px;border-radius:8px;max-width:600px;margin-left:auto;margin-right:auto;'>
                            <h1>✅ Sistema SIGEF Formatado!</h1>
                            <p>Banco zerado e formatado do zero. Todos os dados foram destruídos e as tabelas recriadas.</p>
                            <br><br>
                            <a href='/login' style='background:white; color:#28a745; padding:10px 20px; text-decoration:none; font-weight:bold; border-radius:4px;'>FAZER LOGIN (admin / admin123)</a>
                          </div>";
                    exit();
                } catch (\Exception $e) { 
                    die("Erro fatal na formatação: " . $e->getMessage()); 
                }
            }
        }

        // 👁️ Renderiza a View (Se não for POST)
        $users = $db->query("SELECT id, name, username, role, origem_setor FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin_users.php';
    }

    // ❌ Eliminar Utilizador (Via GET)
    public function deleteUser() {
        $this->checkAdminAccess();
        $id = $_GET['id'] ?? 0;
        
        // Garante que o ID não é do admin master antes de deletar
        Database::getConnection()->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'")->execute([$id]);
        
        header("Location: /admin/users"); 
        exit();
    }
}
