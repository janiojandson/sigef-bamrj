<?php
/**
 * FRONT CONTROLLER - ASSINADOR BAMRJ
 * Versão Final Consolidada: Fase 17 (Edição e Radar Ativos)
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
    $file_strict = $base_dir . $path . '.php';
    
    $path_parts = explode('/', $path);
    if (count($path_parts) > 1) { $path_parts[0] = strtolower($path_parts[0]); }
    $file_fallback = $base_dir . implode('/', $path_parts) . '.php';

    if (file_exists($file_strict)) { require $file_strict; } 
    elseif (file_exists($file_fallback)) { require $file_fallback; }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    // ---- VIEWS ----
    case '/':
    case '/index':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']) { header("Location: /setup_password"); exit(); }
        require __DIR__ . '/../app/views/dashboard.php';
        break;

    case '/login': require __DIR__ . '/../app/views/login.php'; break;
    case '/setup_password': if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); } require __DIR__ . '/../app/views/setup_password.php'; break;
    case '/arquivo': if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); } require __DIR__ . '/../app/views/arquivo.php'; break;
    case '/view': if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); } require __DIR__ . '/../app/views/viewer.php'; break;

    // ---- SESSÃO ----
    case '/logout': session_destroy(); header("Location: /login"); exit(); break;
    case '/acesso_publico': \App\Controllers\ArchiveController::simulatePublicAccess(); break;
    case '/toggle_substitute': if (isset($_SESSION['user_id'])) { $_SESSION['is_substitute'] = !($_SESSION['is_substitute'] ?? false); } header("Location: /index"); exit(); break;

    // ---- ADMIN ----
    case '/admin/create_user': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->createUser(); break;
    case '/admin/edit_user': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->editUser(); break;
    case '/admin/delete': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->deleteUser($_GET['id'] ?? 0); break;

    // ---- DOCUMENTOS E MANIPULAÇÃO ----
    case '/upload': $docCtrl = new \App\Controllers\DocumentController(); $docCtrl->uploadProcess(); break;
    case '/cancel': $docCtrl = new \App\Controllers\DocumentController(); $docCtrl->cancelProcess(); break;
    case '/upload_ne': $docCtrl = new \App\Controllers\DocumentController(); $docCtrl->uploadNE(); break;
    
    // 💥 NOVAS ROTAS DE AÇÃO E EDIÇÃO
    case '/process_action': $docCtrl = new \App\Controllers\DocumentController(); $docCtrl->processAction(); break;
    case '/get_pdf': $docCtrl = new \App\Controllers\DocumentController(); $docCtrl->getPdf(); break;
    case '/edit': $docCtrl = new \App\Controllers\DocumentController(); $docCtrl->editProcess(); break;

    // 📡 ROTA DO RADAR
    case '/api/check_inbox':
        header('Content-Type: application/json');
        
        // 🛡️ TRAVA ANTI-CACHE: Obriga o navegador a verificar os dados reais no Servidor
        header('Cache-Control: no-cache, no-store, must-revalidate'); 
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $dashCtrl = new \App\Controllers\DashboardController();
        $dados = $dashCtrl->getDashboardData();
        echo json_encode(['count' => $dados['inbox_count'] ?? 0]);
        exit();
        break;

    // ---- MANUTENÇÃO ----
    case '/reset_secreto_banco_1234': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->resetDatabase(); break;

    default:
        http_response_code(404);
        echo "<h1>404</h1><p>Erro: Rota não encontrada no perímetro do Assinador-BAMRJ.</p>";
        break;
}