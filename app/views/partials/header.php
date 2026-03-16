<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?= $page_title ?? 'Assinador BAMRJ' ?></title>
    <link rel="stylesheet" href="/static/css/style.css">
    <style>
        body { background-color: #f4f7f6; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        .navbar { background-color: #002244; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-bottom: 3px solid #ffcc00; }
        .navbar img { height: 50px; }
        .navbar-links a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; padding: 8px 12px; border-radius: 4px; transition: 0.3s; display: inline-block; }
        .navbar-links a:hover { background-color: #004488; }
        .navbar-links .logout { background-color: #dc3545; }
        .navbar-links .logout:hover { background-color: #c82333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
    </style>
</head>
<body>
    <?php if (!isset($hide_navbar) || !$hide_navbar): ?>
    <nav class="navbar">
        <div class="navbar-logo" style="display: flex; align-items: center; gap: 15px;">
            <img src="/static/img/brasao_bamrj.png" alt="BAMRJ">
            <h2 style="margin: 0; letter-spacing: 1px; font-size: 1.4em;">ASSINADOR ELETRÔNICO</h2>
        </div>
        <div class="navbar-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (($_SESSION['role'] ?? '') !== 'Usuário Comum'): ?>
                    <span style="margin-right: 15px; color: #a1c6ea;">👤 <?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                <?php endif; ?>
                <a href="/logout" class="logout">Sair do Sistema</a>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container">