<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // 1. Tenta capturar a URL de conexão nativa do Railway
                $databaseUrl = getenv('DATABASE_URL');
                
                if ($databaseUrl) {
                    // O PHP precisa "desmontar" a URL do Railway para alimentar o PDO
                    $dbOpts = parse_url($databaseUrl);
                    $host = $dbOpts["host"] ?? 'localhost';
                    $port = $dbOpts["port"] ?? '5432';
                    $user = $dbOpts["user"] ?? 'postgres';
                    $pass = $dbOpts["pass"] ?? '';
                    $db   = ltrim($dbOpts["path"], '/'); // Remove a barra inicial do nome do banco
                } else {
                    // 2. Fallback Tático: Variáveis individuais (caso use ambiente local ou Docker)
                    $host = getenv('PGHOST') ?: '127.0.0.1';
                    $port = getenv('PGPORT') ?: '5432';
                    $db   = getenv('PGDATABASE') ?: 'assinador_bamrj';
                    $user = getenv('PGUSER') ?: 'postgres';
                    $pass = getenv('PGPASSWORD') ?: '';
                }

                // 3. Montagem da Data Source Name (DSN) para PostgreSQL
                $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
                
                // 4. Estabelecimento da Conexão Blindada
                self::$connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Interrompe e avisa se houver erro grave
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna os dados como arrays limpos
                    PDO::ATTR_EMULATE_PREPARES => false // Força a segurança máxima contra SQL Injection
                ]);

            } catch (PDOException $e) {
                // Interceptação e exibição tática do erro sem expor senhas
                http_response_code(500);
                die("<div style='background:#dc3545;color:white;padding:20px;font-family:sans-serif;'>
                        <h1>⚠️ Falha Crítica na Base de Dados Militar</h1>
                        <p>O Comando não conseguiu estabelecer comunicação com o PostgreSQL.</p>
                        <p><b>Diagnóstico PDO:</b> " . htmlspecialchars($e->getMessage()) . "</p>
                        <p><b>Ação Requerida:</b> Verifique se a variável <code>DATABASE_URL</code> está injetada corretamente no painel do Railway e se o serviço de banco de dados está a rodar.</p>
                     </div>");
            }
        }
        return self::$connection;
    }
}