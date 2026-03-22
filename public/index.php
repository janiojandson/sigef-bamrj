<?php
/**
 * FRONT CONTROLLER - SIGEF BAMRJ
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =======================================================================
// 🛡️ TRAVA BLINDADA PARA ARQUIVOS ESTÁTICOS (Força a entrega do Brasão e CSS)
// =======================================================================
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file_path = __DIR__ . $uri;

// Se o arquivo existir fisicamente na pasta public (ex: /static/img/brasao_bamrj.png)
if ($uri !== '/' && file_exists($file_path) && !is_dir($file_path)) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    // Dicionário de formatos permitidos
    $mime_types = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'css'  => 'text/css',
        'js'   => 'application/javascript'
    ];
    
    // Se for uma imagem ou CSS, o PHP entrega o arquivo "na marra" e encerra a rota
    if (array_key_exists($ext, $mime_types)) {
        header('Content-Type: ' . $mime_types[$ext]);
        header('Cache-Control: public, max-age=86400'); // Cache para não piscar a tela
        readfile($file_path);
        exit(); // 🛑 Aborta o script para não carregar o HTML junto com a imagem
    }
}

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

switch ($uri) {
    case '/':
    case '/index':
        $dashCtrl = new \App\Controllers\DashboardController(); $dashCtrl->index(); break;
    case '/login': 
        $auth = new \App\Controllers\AuthController(); $auth->login(); break;
    case '/logout': 
        session_destroy(); header("Location: /login"); exit(); break;

    // ---- 📡 ROTA DO RADAR (ALERTA DE NOVOS ITENS) ----
    case '/api/check_inbox':
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate'); 
        $dashCtrl = new \App\Controllers\DashboardController();
        // A função getInboxCount será criada no DashboardController
        echo json_encode(['count' => method_exists($dashCtrl, 'getInboxCount') ? $dashCtrl->getInboxCount() : 0]);
        exit();

    // ---- ROTAS DE LANÇAMENTO (DE) ----
    case '/de/nova': $deCtrl = new \App\Controllers\DEController(); $deCtrl->create(); break;
    case '/de/store': $deCtrl = new \App\Controllers\DEController(); $deCtrl->store(); break;

    // ---- ROTAS DO OPERADOR (Execução Financeira) ----
    case '/operador/fila': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->fila(); break;
    case '/operador/acao': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->processarAcao(); break;

    // ---- ROTAS DO PROTOCOLO (Fila de Trabalho e Ações) ----
    case '/protocolo/fila': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->fila(); break;
    case '/protocolo/lote': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->verLote(); break;
    case '/protocolo/receber': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->receberItem(); break;
    case '/protocolo/rejeitar': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->rejeitarItem(); break;

    // ---- ROTAS DE ADMINISTRAÇÃO E CADASTRO ----
    case '/admin/users': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->users(); break;
    case '/admin/delete_user': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->deleteUser(); break;
    case '/reset_secreto_banco_1234': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->resetDatabase(); break;

    default:
        http_response_code(404);
        echo "<div style='padding: 20px; font-family: sans-serif; text-align: center;'><h1>404 - Rota Não Encontrada</h1><a href='/'>Voltar ao Início</a></div>";
        break;
}