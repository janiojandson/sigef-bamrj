<?php
// Arquivo temporário: public/migrate.php
require_once __DIR__ . '/../app/core/Database.php';

use App\core\Database;

try {
    $db = Database::getConnection();
    
    // Adicionamos IF NOT EXISTS para não quebrar caso rode duas vezes
    $sql = "
    -- 1. DOCUMENTO DE ENCAMINHAMENTO
    CREATE TABLE IF NOT EXISTS documentos_encaminhamento (
        id SERIAL PRIMARY KEY,
        criado_por INTEGER REFERENCES users(id) ON DELETE RESTRICT,
        origem VARCHAR(64) NOT NULL,
        status_geral VARCHAR(64) DEFAULT 'EM_PREPARACAO',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 2. ITENS DO D.E.
    CREATE TABLE IF NOT EXISTS itens_de (
        id SERIAL PRIMARY KEY,
        de_id INTEGER REFERENCES documentos_encaminhamento(id) ON DELETE CASCADE,
        nome_documento VARCHAR(255) NOT NULL,
        ns_pa VARCHAR(128),
        np VARCHAR(128),
        status_lf VARCHAR(64) DEFAULT 'AGUARDANDO',
        op VARCHAR(128),
        ob VARCHAR(128),
        data_pagamento DATE,
        caminho_pdf_ob VARCHAR(512),
        status_item VARCHAR(64) DEFAULT 'EM_PREPARACAO',
        motivo_rejeicao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 3. EMPENHOS LEGADO
    CREATE TABLE IF NOT EXISTS empenhos_legado (
        id SERIAL PRIMARY KEY,
        numero_ne VARCHAR(64) UNIQUE NOT NULL,
        ano_empenho INTEGER NOT NULL,
        credor VARCHAR(255) NOT NULL,
        cnpj_cpf VARCHAR(20),
        valor DECIMAL(15,2),
        cadastrado_por INTEGER REFERENCES users(id) ON DELETE SET NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 4. AUDITORIA
    CREATE TABLE IF NOT EXISTS auditoria (
        id SERIAL PRIMARY KEY,
        de_id INTEGER REFERENCES documentos_encaminhamento(id) ON DELETE CASCADE,
        item_id INTEGER REFERENCES itens_de(id) ON DELETE CASCADE,
        usuario_nome VARCHAR(128) NOT NULL,
        perfil VARCHAR(64) NOT NULL,
        acao VARCHAR(128) NOT NULL,
        justificativa TEXT,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $db->exec($sql);
    
    echo "<div style='font-family: Arial; padding: 20px; text-align: center;'>";
    echo "<h2 style='color: #28a745;'>✅ Tabelas da v2.0 (DE, Itens e Auditoria) criadas com sucesso!</h2>";
    echo "<p>Banco de dados atualizado. <b>Lembre-se de apagar este arquivo (migrate.php) por segurança.</b></p>";
    echo "<a href='/dashboard' style='padding: 10px 20px; background: #004488; color: white; text-decoration: none; border-radius: 5px;'>Ir para o Dashboard</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family: Arial; padding: 20px; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb;'>";
    echo "<h2>❌ Erro ao criar tabelas:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}