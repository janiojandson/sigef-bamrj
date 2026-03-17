<?php
/**
 * FRONT CONTROLLER - SIGEF BAMRJ v2.0
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $path = str_replace('\\', '/', $relative_class);
    $file = $base_dir . $path . '.php';
    if (file_exists($file)) { require $file; }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/':
    case '/login':
        if (isset($_SESSION['user_id'])) { header("Location: /dashboard"); exit(); }
        require __DIR__ . '/../app/views/login.php'; 
        break;

    case '/logout':
        $auth = new \App\Controllers\AuthController();
        $auth->logout();
        break;

    case '/dashboard':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        require __DIR__ . '/../app/views/dashboard.php';
        break;

    // A rota do Admin para criar novos usuários (vamos reciclá-la)
    case '/admin/create_user': 
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $adminCtrl = new \App\Controllers\AdminController(); 
        $adminCtrl->createUser(); 
        break;

    default:
        http_response_code(404);
        echo "<h1 style='text-align:center; margin-top:50px; color:#002244;'>404 - Rota não encontrada no SIGEF</h1>";
        break;
}