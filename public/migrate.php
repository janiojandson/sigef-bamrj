<?php
/**
 * Script de Migração Automática — SIGEF BAMRJ
 * ACESSO RESTRITO: Remove este ficheiro após a execução!
 */
session_start();

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $path = str_replace('\\', '/', $relative_class);
    $file_strict = $base_dir . $path . '.php';
    $path_parts = explode('/', $path);
    if (count($path_parts) > 1) { $path_parts[0] = strtolower($path_parts[0]); }
    $file_fallback = $base_dir . implode('/', $path_parts) . '.php';
    if (file_exists($file_strict)) { require $file_strict; } 
    elseif (file_exists($file_fallback)) { require $file_fallback; }
});

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