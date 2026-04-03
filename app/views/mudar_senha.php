<?php
$page_title = 'Mudar Senha - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 450px; border-top: 5px solid #004488;">
        <h2 style="color: #002244; margin-top: 0;">🔑 Alterar Senha de Acesso</h2>
        
        <p style="color: #666; font-size: 0.95em; margin-bottom: 20px;">
            <?php if ($_SESSION['must_change_password'] ?? false): ?>
                <strong style="color: #dc3545;">⚠️ Ação Necessária:</strong> Este é o seu primeiro acesso. Por questões de segurança orgânica, você deve criar uma senha pessoal e intransferível agora.
            <?php else: ?>
                Atualize sua senha de acesso ao sistema SIGEF.
            <?php endif; ?>
        </p>

        <?php if (!empty($error)): ?>
            <p style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; border: 1px solid #c3e6cb; font-weight: bold;"><?= htmlspecialchars($success) ?></p>
        <?php else: ?>
            <form method="POST">
                <label style="font-weight: bold; color: #333;">Senha Atual (Provisória):</label>
                <input type="password" name="senha_atual" required style="width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; border-radius: 4px;">

                <label style="font-weight: bold; color: #333;">Nova Senha Pessoal:</label>
                <input type="password" name="nova_senha" required style="width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; border-radius: 4px;">

                <label style="font-weight: bold; color: #333;">Confirmar Nova Senha:</label>
                <input type="password" name="confirma_senha" required style="width: 100%; padding: 10px; margin: 5px 0 20px 0; border: 1px solid #ccc; border-radius: 4px;">

                <button type="submit" style="width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1.1em;">💾 Salvar e Acessar</button>
            </form>
        <?php endif; ?>
        
        <?php if (!($_SESSION['must_change_password'] ?? false) && empty($success)): ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="/" style="color: #6c757d; text-decoration: none; font-weight: bold;">⬅️ Cancelar e Voltar</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
