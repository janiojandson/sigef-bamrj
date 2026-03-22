<?php
$page_title = 'Acesso Restrito - SIGEF BAMRJ';
$hide_navbar = true; // Oculta a barra do topo na tela de login
require __DIR__ . '/partials/header.php';
?>
<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; border-top: 5px solid #002244;">
        
        <img src="/static/img/brasao_bamrj.png" alt="BAMRJ" style="width: 100px; margin-bottom: 20px;">
        <h2 style="color: #002244; margin-top: 0;">SIGEF BAMRJ</h2>
        
        <?php if (!empty($error)): ?>
            <p style="color: #721c24; font-weight: bold; background: #f8d7da; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="NIP / Utilizador" required 
                   style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            <input type="password" name="password" placeholder="Senha" required 
                   style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            
            <button type="submit" style="width: 100%; padding: 12px; background-color: #002244; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-bottom: 15px; font-size: 1.1em;">
                ENTRAR NO SISTEMA
            </button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>