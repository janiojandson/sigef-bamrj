<?php
/**
 * Script de Migração Automática — SIGEF BAMRJ
 * Executa as migrações SQL necessárias para as novas features.
 * ACESSO RESTRITO: Remove este ficheiro após a execução!
 */
require __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

echo "<h1>🔄 Migração do SIGEF BAMRJ</h1><pre>";

try {
    $db = Database::getConnection();
    
    $migrations = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS substituto_ativo BOOLEAN DEFAULT FALSE",
        "ALTER TABLE de_lotes ADD COLUMN IF NOT EXISTS data_envio_protocolo TIMESTAMP",
        "UPDATE de_lotes SET data_envio_protocolo = criado_em WHERE data_envio_protocolo IS NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS origem_setor VARCHAR(128) DEFAULT 'BAMRJ'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN DEFAULT TRUE",
        "ALTER TABLE de_itens ADD COLUMN IF NOT EXISTS empresa_nome VARCHAR(255) DEFAULT 'Não Informado'",
    ];
    
    foreach ($migrations as $i => $sql) {
        try {
            $db->exec($sql);
            echo "✅ Migração " . ($i + 1) . ": OK\n";
        } catch (PDOException $e) {
            echo "⚠️ Migração " . ($i + 1) . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Todas as migrações foram processadas!\n";
    echo "⚠️ DELETE este ficheiro (migrate.php) do servidor após a execução.\n";
    
} catch (Exception $e) {
    echo "❌ Erro crítico: " . $e->getMessage() . "\n";
}

echo "</pre>";