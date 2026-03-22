// Adicione isto no seu public/index.php dentro do switch($uri):

    // ---- SIGEF: ROTA DE LANÇAMENTO (DE) ----
    case '/de/nova': 
        $deCtrl = new \App\Controllers\DEController(); 
        $deCtrl->create(); 
        break;
        
    case '/de/store': 
        $deCtrl = new \App\Controllers\DEController(); 
        $deCtrl->store(); 
        break;