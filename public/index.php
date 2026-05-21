<?php
/**
 * FRONT CONTROLLER - SIGEF BAMRJ
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =======================================================================
// 🛡️ TRAVA BLINDADA PARA ARQUIVOS ESTÁTICOS
// =======================================================================
$uri_raw = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file_path = __DIR__ . $uri_raw;

if ($uri_raw !== '/' && file_exists($file_path) && !is_dir($file_path)) {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    $mime_types = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'pdf'  => 'application/pdf'
    ];
    
    if (array_key_exists($ext, $mime_types)) {
        header('Content-Type: ' . $mime_types[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($file_path);
        exit();
    }
}

session_start();

// 🛡️ FIREWALL: Trava o usuário na tela de mudança de senha se for o primeiro acesso
if (isset($_SESSION['user_id']) && !empty($_SESSION['must_change_password'])) {
    $allowed_uris = ['/mudar_senha', '/logout'];
    if (!in_array($uri_raw, $allowed_uris)) {
        header("Location: /mudar_senha");
        exit();
    }
}

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

// 🛡️ LIMPEZA DE ROTA CONTRA 404
$uri = rtrim($uri_raw, '/');
if ($uri === '') $uri = '/';

switch ($uri) {
    case '/':
    case '/index': $dashCtrl = new \App\Controllers\DashboardController(); $dashCtrl->index(); break;
    case '/login': $auth = new \App\Controllers\AuthController(); $auth->login(); break;
    case '/logout': session_destroy(); header("Location: /login"); exit(); break;
    case '/mudar_senha': $auth = new \App\Controllers\AuthController(); $auth->mudarSenha(); break;

    case '/api/check_inbox':
        header('Content-Type: application/json');
        $dashCtrl = new \App\Controllers\DashboardController();
        echo json_encode(['count' => method_exists($dashCtrl, 'getInboxCount') ? $dashCtrl->getInboxCount() : 0]);
        exit();
        break;

    // 🔄 ROTA DE MIGRAÇÃO (Acesso restrito — remover após uso)
    case '/migrate':
        require __DIR__ . '/migrate.php';
        break;

    // 📄 ROTAS DE DESPESA (DE)
    case '/de/create':
    case '/de/nova':
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->create(); break;
    case '/de/store':
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->store(); break;
    case '/de/acompanhar':
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->acompanhar(); break;
    case '/de/reenviar':
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->reenviar(); break;
    case '/de/excluir_item':
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->excluirItem(); break;
    case '/de/desarquivar':
        $deCtrl = new \App\Controllers\DEController(); $deCtrl->desarquivar(); break;

    // 📋 ROTAS DO PROTOCOLO
    case '/protocolo/fila':
        $protoCtrl = new \App\Controllers\ProtocoloController(); $protoCtrl->fila(); break;
    case '/protocolo/lote':
    case '/protocolo/ver_lote':
        $protoCtrl = new \App\Controllers\ProtocoloController(); $protoCtrl->verLote(); break;
    case '/protocolo/receber':
        $protoCtrl = new \App\Controllers\ProtocoloController(); $protoCtrl->receberItem(); break;
    case '/protocolo/rejeitar':
        $protoCtrl = new \App\Controllers\ProtocoloController();
        // [ANTES]
        // if(method_exists($protoCtrl, 'rejeitarItem')) {
        //     $protoCtrl->rejeitarItem();
        // } elseif(method_exists($protoCtrl, 'devolverItem')) {
        //     $protoCtrl->devolverItem();
        // }
        // [DEPOIS] Chama diretamente o novo método rejeitarItem() que agora existe
        if(method_exists($protoCtrl, 'rejeitarItem')) {
            $protoCtrl->rejeitarItem();
        } else {
            $protoCtrl->devolverItem();
        }
        break;
    case '/protocolo/imprimir_capa':
        $protoCtrl = new \App\Controllers\ProtocoloController(); $protoCtrl->imprimirCapa(); break;

    // ⚙️ ROTAS DO OPERADOR
    case '/operador/fila':
        $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->fila(); break;
    case '/operador/acao':
        $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->processarAcao(); break;
    case '/operador/gerar_rap':
        $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->gerarRapLote(); break;
    case '/operador/monitoramento':
        $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->monitoramento(); break;
    case '/operador/imprimir_rap':
        $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->imprimirRap(); break;
    case '/operador/excluir_rap':
        $opCtrl = new \App\Controllers\OperadorController(); $opCtrl->excluirRap(); break;

    // ✍️ ROTAS DO ASSINADOR
    case '/assinador/fila':
        $assCtrl = new \App\Controllers\AssinadorController(); $assCtrl->fila(); break;
    case '/assinador/acao':
        $assCtrl = new \App\Controllers\AssinadorController(); $assCtrl->processarAcao(); break;
    case '/assinador/gerar_rap':
        $assCtrl = new \App\Controllers\AssinadorController(); $assCtrl->gerarRapLote(); break;
    case '/assinador/toggleSubstituto':
        $assCtrl = new \App\Controllers\AssinadorController(); $assCtrl->toggleSubstituto(); break;

    // 📊 ROTAS DE RELATÓRIO
    case '/relatorio/ob':
    case '/relatorio':
        $relCtrl = new \App\Controllers\RelatorioController(); $relCtrl->ob(); break;

    // 👨‍💼 ROTAS DO ADMIN
    case '/admin/users':
        $adminCtrl = new \App\Controllers\AdminController(); $adminCtrl->users(); break;
    case '/admin/delete_user': 
        $adminCtrl = new \App\Controllers\AdminController(); 
        if(method_exists($adminCtrl, 'deleteUser')) { $adminCtrl->deleteUser(); }
        break;
    case '/admin/limpar_dados': 
        $adminCtrl = new \App\Controllers\AdminController(); 
        if(method_exists($adminCtrl, 'limparDados')) { $adminCtrl->limparDados(); }
        break;
    case '/admin/upgrade_db': 
        $adminCtrl = new \App\Controllers\AdminController(); 
        if(method_exists($adminCtrl, 'upgradeDatabase')) { $adminCtrl->upgradeDatabase(); }
        break;
    // 📖 HISTÓRICO AVULSO E API
    case '/historico/item':
        $histCtrl = new \App\Controllers\HistoricoController(); $histCtrl->ver(); break;
    case '/historico/api':
        $histCtrl = new \App\Controllers\HistoricoController(); $histCtrl->api(); break;

    default:
        http_response_code(404);
        echo "<h1>404 - Página não encontrada</h1><p>A rota <code>{$uri}</code> não existe.</p><a href='/'>Voltar ao início</a>";
        break;
}