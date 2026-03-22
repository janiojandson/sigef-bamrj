<?php
/**
 * FRONT CONTROLLER - SIGEF BAMRJ
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---- 🛡️ DESVIO EXPRESSO PARA ARQUIVOS ESTÁTICOS (CORRIGE O BRASÃO) ----
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js)$/', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        $mime = mime_content_type($file);
        if (str_ends_with($uri, '.css')) $mime = 'text/css';
        if (str_ends_with($uri, '.js')) $mime = 'application/javascript';
        header("Content-Type: $mime");
        readfile($file);
        exit();
    }
}

session_start();

// Autoload tático
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

// Mapeamento de Rotas
switch ($uri) {
    case '/':
    case '/index':
        $dashCtrl = new \App\Controllers\DashboardController(); $dashCtrl->index(); break;
    case '/login': 
        $auth = new \App\Controllers\AuthController(); $auth->login(); break;
    case '/logout': 
        session_destroy(); header("Location: /login"); exit(); break;

    // ---- ROTAS DA DE (Múltiplos Itens) ----
    case '/de/nova': 
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->create(); break;
    case '/de/store': 
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->store(); break;

    // ---- MANUTENÇÃO E ADMINISTRAÇÃO ----
    case '/reset_secreto_banco_1234': 
        $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->resetDatabase(); break;

    default:
        http_response_code(404);
        echo "<div style='padding: 20px; font-family: sans-serif; text-align: center;'><h1>404 - Rota Não Encontrada</h1><a href='/'>Voltar ao Início</a></div>";
        break;
}