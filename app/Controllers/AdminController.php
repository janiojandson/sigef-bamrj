<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class AdminController {
    
    // 💣 ROTA SECRETA: Construtor do Banco de Dados SIGEF
    public function resetDatabase() {
        $db = Database::getConnection();
        try {
            // 1. Destruição Tática (Limpa o sistema antigo e eventuais conflitos)
            $db->exec("DROP TABLE IF EXISTS de_eventos CASCADE;");
            $db->exec("DROP TABLE IF EXISTS de_itens CASCADE;");
            $db->exec("DROP TABLE IF EXISTS de_lotes CASCADE;");
            $db->exec("DROP TABLE IF EXISTS document_files CASCADE;"); 
            $db->exec("DROP TABLE IF EXISTS events CASCADE;");         
            $db->exec("DROP TABLE IF EXISTS documents CASCADE;");      
            $db->exec("DROP TABLE IF EXISTS users CASCADE;");          

            // 2. Reconstrução Estrutural (A Nova Arquitetura do SIGEF)
            $db->exec("
                -- A. Tabela de Controle de Acesso (Login)
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(128) NOT NULL,
                    username VARCHAR(64) UNIQUE NOT NULL,
                    password_hash VARCHAR(256) NOT NULL,
                    role VARCHAR(64) NOT NULL,
                    must_change_password BOOLEAN DEFAULT TRUE
                );

                -- B. Lotes de Encaminhamento (Capa da DE)
                CREATE TABLE de_lotes (
                    id SERIAL PRIMARY KEY,
                    numero_geral VARCHAR(32) UNIQUE NOT NULL,
                    origem_tipo VARCHAR(20) NOT NULL,
                    status_lote VARCHAR(64) DEFAULT 'EM_ELABORACAO',
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    criado_por VARCHAR(64)
                );

                -- C. Itens da DE (A Granularidade Tática)
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

                -- D. Eventos (Livro de Socorro / Auditoria)
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

            // 3. Criação do Master Admin para o primeiro acesso
            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, false)");
            $stmt->execute(['Administrador', 'admin', $hash, 'Admin']);

            echo "<div style='background:#004488;color:white;padding:30px;font-family:sans-serif;text-align:center;border-bottom: 5px solid #ffcc00;'>
                    <h1>✅ OPERAÇÃO BEM-SUCEDIDA!</h1>
                    <p>Senhor, a Base de Dados do SIGEF-BAMRJ foi injetada com sucesso via PHP.</p>
                    <p>Tabelas criadas: <b>users, de_lotes, de_itens, de_eventos</b>.</p>
                    <br>
                    <a href='/login' style='display:inline-block;background:#28a745;color:white;padding:15px 25px;text-decoration:none;font-weight:bold;border-radius:5px;'>IR PARA O LOGIN TÁTICO</a>
                  </div>";
        } catch (\Exception $e) {
            echo "<div style='background:#dc3545;color:white;padding:30px;font-family:sans-serif;text-align:center;'>
                    <h1>⚠️ Falha na Injeção Estrutural</h1>
                    <p>" . htmlspecialchars($e->getMessage()) . "</p>
                  </div>";
        }
    }
}