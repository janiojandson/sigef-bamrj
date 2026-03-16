<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Script de Instalação Tática do SIGEF
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

try {
    echo "<h1>Iniciando Operação de Construção do SIGEF...</h1>";
    $db = Database::getConnection();

    // O nosso SQL oficial
    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(128) NOT NULL,
            username VARCHAR(64) UNIQUE NOT NULL,
            password_hash VARCHAR(256) NOT NULL,
            role VARCHAR(64) NOT NULL, 
            unit_omap VARCHAR(64), 
            must_change_password BOOLEAN DEFAULT TRUE
        );

        CREATE TABLE IF NOT EXISTS documentos_encaminhamento (
            id SERIAL PRIMARY KEY,
            protocolo VARCHAR(32) UNIQUE NOT NULL,
            tipo_processo VARCHAR(30) NOT NULL DEFAULT 'PAGAMENTO',
            origem_tipo VARCHAR(20) NOT NULL, 
            origem_nome VARCHAR(128) NOT NULL,
            solemp VARCHAR(50), 
            status_geral VARCHAR(64) DEFAULT 'EM_PREPARACAO',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            criado_por INTEGER REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS rap (
            id SERIAL PRIMARY KEY,
            numero_rap VARCHAR(32) UNIQUE NOT NULL, 
            status_assinatura VARCHAR(64) DEFAULT 'CAIXA_ENC_FINANCAS',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            criado_por INTEGER REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS itens_de (
            id SERIAL PRIMARY KEY,
            de_id INTEGER REFERENCES documentos_encaminhamento(id) ON DELETE CASCADE,
            rap_id INTEGER REFERENCES rap(id) ON DELETE SET NULL, 
            cnpj VARCHAR(20) NOT NULL,           
            numero_nf VARCHAR(50) NOT NULL,      
            ns_pa VARCHAR(50) NOT NULL,          
            valor DECIMAL(15, 2) NOT NULL,       
            status_item VARCHAR(64) DEFAULT 'ENVIADO_PROTOCOLO', 
            np_numero VARCHAR(50),               
            status_lf VARCHAR(20) DEFAULT 'AGUARDANDO', 
            op_numero VARCHAR(50),               
            ob_numero VARCHAR(50),               
            data_pagamento DATE,
            caminho_pdf VARCHAR(256), 
            ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS auditoria (
            id SERIAL PRIMARY KEY,
            de_id INTEGER REFERENCES documentos_encaminhamento(id) ON DELETE CASCADE,
            item_id INTEGER REFERENCES itens_de(id) ON DELETE CASCADE, 
            rap_id INTEGER REFERENCES rap(id) ON DELETE CASCADE,
            usuario_nome VARCHAR(128) NOT NULL,
            perfil VARCHAR(64) NOT NULL,
            acao VARCHAR(64) NOT NULL, 
            justificativa TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";

    // Executa a criação das tabelas
    $db->exec($sql);
    echo "<p style='color:green;'>✅ Tabelas do Sistema SIGEF criadas com sucesso!</p>";

    // Cria o usuário Admin padrão
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    
    // Verifica se o admin já existe para não dar erro
    $check = $db->query("SELECT id FROM users WHERE username = 'admin'")->fetch();
    if (!$check) {
        $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, false)");
        $stmt->execute(['Administrador Geral', 'admin', $hash, 'Admin']);
        echo "<p style='color:blue;'>🛡️ Utilizador 'admin' (Senha: admin123) criado com sucesso.</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Utilizador 'admin' já existia. Nenhuma alteração feita.</p>";
    }

    echo "<h3>A Base de Dados está 100% Operacional, Comandante!</h3>";
    echo "<p>Pode excluir este ficheiro (instalar.php) por segurança.</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERRO CRÍTICO: " . $e->getMessage() . "</p>";
}
?>