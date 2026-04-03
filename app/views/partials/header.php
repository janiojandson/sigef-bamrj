<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title><?= $page_title ?? 'SIGEF BAMRJ' ?></title>
    <link rel="stylesheet" href="/static/css/style.css">
    <style>
        body { background-color: #f4f7f6; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        
        /* Barra Superior Padrão SIGEF/Assinador */
        .navbar { background-color: #002244; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-bottom: 4px solid #ffcc00; }
        .navbar-logo img { height: 45px; background: white; padding: 3px; border-radius: 4px; }
        .navbar-links { display: flex; align-items: center; gap: 15px; }
        .navbar-links a { color: white; text-decoration: none; font-weight: bold; padding: 8px 12px; border-radius: 4px; transition: 0.3s; display: inline-block; }
        
        /* Botão Sair Vermelho */
        .navbar-links .logout { background-color: #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .navbar-links .logout:hover { background-color: #c82333; }
        
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }

        /* =========================================================
           PADRONIZAÇÃO DE BOTÕES E BADGES (Estilo Assinador)
        ========================================================= */
        .btn { display: inline-block; padding: 8px 16px; font-size: 0.95em; font-weight: bold; text-align: center; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.15); }
        
        .btn-primary { background: #004488; color: white; }
        .btn-primary:hover { background: #003366; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffcc00; color: #002244; }
        .btn-warning:hover { background: #e6b800; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        /* Badges e Tags */
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; display: inline-block; }
        .badge-alerta { background: #dc3545; color: white; }
        .badge-aviso { background: #ffc107; color: #000; }
        
        /* Tabelas Responsivas */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { padding: 12px 15px; background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #00447c; text-align: left; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <?php if (!isset($hide_navbar) || !$hide_navbar): ?>
    <nav class="navbar">
        <div class="navbar-logo" style="display: flex; align-items: center; gap: 15px;">
            <img src="/static/img/brasao_bamrj.png" alt="BAMRJ">
            <h2 style="margin: 0; letter-spacing: 1px; font-size: 1.2em; text-transform: uppercase;">
                SIGEF BAMRJ
            </h2>
        </div>
        <div class="navbar-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; color: #a1c6ea;">👤 <?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                <a href="/mudar_senha" style="background: transparent; border: 1px solid #a1c6ea;">🔑 Mudar Senha</a>
                <a href="/logout" class="logout">Sair do Sistema</a>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container">
