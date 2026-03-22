<?php
/**
 * FRONT CONTROLLER - SIGEF BAMRJ
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Autoload tático das classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $path = str_replace('\\', '/', $relative_class);
    $file = $base_dir . $path . '.php';
    if (file_exists($file)) require $file;
});

// Captura a URL digitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Mapeamento de Rotas
switch ($uri) {
    case '/':
    case '/index':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        echo "<h1>Bem-vindo ao SIGEF-BAMRJ, " . htmlspecialchars($_SESSION['name']) . "!</h1>";
        echo "<a href='/de/nova'>Criar Nova DE</a> | <a href='/logout'>Sair</a>";
        break;

    case '/login': 
        $auth = new \App\Controllers\AuthController(); 
        $auth->login(); 
        break;

    case '/logout': 
        session_destroy(); 
        header("Location: /login"); 
        exit(); 
        break;

    // ---- ROTAS DA DE (Documento de Encaminhamento) ----
    case '/de/nova': 
        $deCtrl = new \App\Controllers\DEController(); 
        $deCtrl->create(); 
        break;
        
    case '/de/store': 
        $deCtrl = new \App\Controllers\DEController(); 
        $deCtrl->store(); 
        break;

    // ---- ROTA SECRETA DE CONSTRUÇÃO DO BANCO ----
    case '/reset_secreto_banco_1234': 
        $adminCtrl = new \App\Controllers\AdminController(); 
        $adminCtrl->resetDatabase(); 
        break;

    default:
        http_response_code(404);
        echo "<h1>404 - Rota não encontrada</h1>";
        break;
}