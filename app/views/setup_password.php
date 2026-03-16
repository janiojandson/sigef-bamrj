<?php
$page_title = 'Configuração de Senha - Assinador BAMRJ';
$hide_navbar = true; // Oculta o menu para focar na troca de senha
require __DIR__ . '/partials/header.php';

// Instancia o AuthController para processar a troca de senha
$auth = new \App\Controllers\AuthController();
// Tenta rodar a função de troca (se o seu método tiver outro nome, ajuste aqui, ex: changePassword)
$error = method_exists($auth, 'setupPassword') ? $auth->setupPassword() : ''; 
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 20px;">
    
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); width: 100%; max-width: 450px; text-align: center; border-top: 5px solid #ffcc00;">
        
        <img src="/static/img/brasao_bamrj.png" alt="BAMRJ" style="width: 90px; margin-bottom: 15px;">
        
        <h2 style="color: #002244; margin-top: 0; margin-bottom: 5px;">Ação Obrigatória</h2>
        <p style="color: #666; font-size: 0.95em; margin-bottom: 25px; line-height: 1.5;">
            Por motivos de segurança, o senhor(a) precisa cadastrar uma <b>Nova Senha Pessoal</b> antes de acessar o Assinador Eletrônico.
        </p>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; border: 1px solid #f5c6cb; margin-bottom: 20px; font-weight: bold; font-size: 0.9em;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="text-align: left;">
            <label style="color: #002244; font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 8px;">🔑 Nova Senha:</label>
            <input type="password" name="new_password" required placeholder="Digite a nova senha"
                   style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1em; background: #f8f9fa;">
            
            <label style="color: #002244; font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 8px;">🔁 Confirme a Nova Senha:</label>
            <input type="password" name="confirm_password" required placeholder="Repita a nova senha"
                   style="width: 100%; padding: 12px; margin-bottom: 25px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1em; background: #f8f9fa;">
            
            <button type="submit" style="width: 100%; padding: 14px; background-color: #002244; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1.1em; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                💾 SALVAR E ACESSAR O SISTEMA
            </button>
        </form>
        
        <div style="margin-top: 25px; font-size: 0.9em;">
            <a href="/logout" style="color: #dc3545; text-decoration: none; font-weight: bold; padding: 8px 15px; border-radius: 4px; border: 1px solid #dc3545; transition: 0.2s;">
                🚪 Cancelar e Sair
            </a>
        </div>
        
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>