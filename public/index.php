<?php
/**
 * FRONT CONTROLLER - SIGEF BAMRJ
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$uri_raw = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file_path = __DIR__ . $uri_raw;

if ($uri_raw !== '/' && file_exists($file_path) && !is_dir($file_path)) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_types = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml','css'=>'text/css','js'=>'application/javascript'];
    if (array_key_exists($ext, $mime_types)) {
        header('Content-Type: ' . $mime_types[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($file_path); exit();
    }
}

session_start();

spl_autoload_register(function ($class) {
    $prefix = 'App\\'; $base_dir = __DIR__ . '/../app/'; $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len); $path = str_replace('\\', '/', $relative_class);
    $file_strict = $base_dir . $path . '.php';
    $path_parts = explode('/', $path); if (count($path_parts) > 1) { $path_parts[0] = strtolower($path_parts[0]); }
    $file_fallback = $base_dir . implode('/', $path_parts) . '.php';
    if (file_exists($file_strict)) { require $file_strict; } elseif (file_exists($file_fallback)) { require $file_fallback; }
});

$uri = rtrim($uri_raw, '/');
if ($uri === '') $uri = '/';

switch ($uri) {
    case '/':
    case '/index': $dashCtrl = new \App\Controllers\DashboardController(); $dashCtrl->index(); break;
    case '/login': $auth = new \App\Controllers\AuthController(); $auth->login(); break;
    case '/logout': session_destroy(); header("Location: /login"); exit(); break;

    case '/api/check_inbox':
        header('Content-Type: application/json'); $dashCtrl = new \App\Controllers\DashboardController();
        echo json_encode(['count' => method_exists($dashCtrl, 'getInboxCount') ? $dashCtrl->getInboxCount() : 0]); exit();

    case '/de/nova': $deCtrl = new \App\Controllers\DEController(); $deCtrl->create(); break;
    case '/de/store': $deCtrl = new \App\Controllers\DEController(); $deCtrl->store(); break;
    case '/de/acompanhar': $deCtrl = new \App\Controllers\DEController(); $deCtrl->acompanhar(); break;
    case '/de/reenviar': $deCtrl = new \App\Controllers\DEController(); $deCtrl->reenviar(); break;
    case '/de/excluir_item': $deCtrl = new \App\Controllers\DEController(); $deCtrl->excluirItem(); break;

    case '/operador/fila': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->fila(); break;
    case '/operador/acao': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->processarAcao(); break;
    case '/operador/gerar_rap': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->gerarRapLote(); break;
    case '/operador/monitoramento': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->monitoramento(); break; 
    case '/operador/imprimir_rap': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->imprimirRap(); break;
    case '/operador/excluir_rap': $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->excluirRap(); break;

    case '/protocolo/fila': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->fila(); break;
    case '/protocolo/lote': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->verLote(); break;
    case '/protocolo/receber': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->receberItem(); break;
    case '/protocolo/rejeitar': $protCtrl = new \App\Controllers\ProtocoloController(); $protCtrl->rejeitarItem(); break;

    case '/assinador/lote': $assCtrl = new \App\Controllers\AssinadorController(); $assCtrl->verLote(); break;
    case '/assinador/acao': $assCtrl = new \App\Controllers\AssinadorController(); $assCtrl->processarAcao(); break;

    case '/relatorio/ob': $relCtrl = new \App\Controllers\RelatorioController(); $relCtrl->index(); break;

    case '/admin/users': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->users(); break;
    case '/admin/delete_user': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->deleteUser(); break;
    case '/admin/limpar_dados': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->limparDados(); break;
    case '/admin/upgrade_db': $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->upgradeDatabase(); break;

    default:
        http_response_code(404); echo "<div style='padding: 20px; text-align: center;'><h1>404 - Rota Não Encontrada</h1><a href='/'>Voltar</a></div>"; break;
}