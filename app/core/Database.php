<?php
namespace App\core;
use PDO;
use PDOException;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $databaseUrl = getenv('DATABASE_URL');
                if ($databaseUrl) {
                    $dbOpts = parse_url($databaseUrl);
                    $host = $dbOpts["host"];
                    $port = $dbOpts["port"];
                    $user = $dbOpts["user"];
                    $pass = $dbOpts["pass"];
                    $db   = ltrim($dbOpts["path"], '/');
                } else {
                    die("DATABASE_URL não configurada no Railway.");
                }

                $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
                self::$connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                die("Erro na Base de Dados: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
}