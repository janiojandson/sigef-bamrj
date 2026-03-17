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
        // Redirecionamento Inteligente com base no perfil
        if ($_SESSION['role'] === 'OMAP' || $_SESSION['role'] === 'SETOR_INTERNO') {
            header("Location: /omap/painel"); exit();
        } elseif ($_SESSION['role'] === 'Operador') {
            header("Location: /operador/painel"); exit();
        } elseif ($_SESSION['role'] === 'Protocolo') {
            header("Location: /protocolo/painel"); exit(); // Rota do Protocolo
        }
        require __DIR__ . '/../app/views/dashboard.php';
        break;

    case '/admin/create_user': 
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $adminCtrl = new \App\Controllers\AdminController(); 
        $adminCtrl->createUser(); 
        break;

    // ROTAS DA OMAP
    case '/omap/painel':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $omapCtrl = new \App\Controllers\OmapController();
        $omapCtrl->painel();
        break;
    case '/omap/criar_de':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $omapCtrl = new \App\Controllers\OmapController();
        $omapCtrl->criarDE();
        break;

    // ROTAS DO PROTOCOLO (NOVO)
    case '/protocolo/painel':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $protCtrl = new \App\Controllers\ProtocoloController();
        $protCtrl->painel();
        break;
    case '/protocolo/encaminhar':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $protCtrl = new \App\Controllers\ProtocoloController();
        $protCtrl->encaminhar();
        break;

    // ROTAS DO OPERADOR
    case '/operador/painel':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $opCtrl = new \App\Controllers\OperadorController();
        $opCtrl->painel();
        break;
    case '/operador/veto':
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $opCtrl = new \App\Controllers\OperadorController();
        $opCtrl->aplicarVeto();
        break;

    default:
        http_response_code(404);
        echo "<h1 style='text-align:center; margin-top:50px; color:#002244;'>404 - Rota não encontrada no SIGEF</h1>";
        break;
}