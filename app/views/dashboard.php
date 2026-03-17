<?php
$page_title = 'Dashboard - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';

$dashController = new \App\Controllers\DashboardController();
$dados = $dashController->getDashboardData();

$role = $dados['role'];
?>

<div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #004488; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">Painel de Comando | <span style="color: #666; font-size: 0.8em;">Perfil: <?= htmlspecialchars($role) ?></span></h2>
</div>

<?php if ($role === 'Admin'): ?>
<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #dc3545;">
    <h3 style="margin-top: 0; color: #002244;">🛡️ Gestão de Utilizadores (SIGEF v2.0)</h3>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ddd;">
        <h4 style="margin-top:0; color: #333;">➕ Cadastrar Novo Perfil</h4>
        <form action="/admin/create_user" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="name" placeholder="Nome / Guerra" required style="padding: 10px; flex: 1.5; border: 1px solid #ccc; border-radius: 4px;">
            <input type="text" name="username" placeholder="NIP / Login" required style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
            <input type="password" name="password" placeholder="Senha Inicial" required style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
            <input type="text" name="unit_omap" placeholder="Sigla OMAP (Ex: OMAP-CX)" style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;" title="Preencha apenas se for perfil OMAP">
            <select name="role" required style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
                <option value="Operador">Operador</option>
                <option value="OMAP">OMAP (Unidade Externa)</option>
                <option value="SETOR_INTERNO">Setor Interno</option>
                <option value="Enc_Financas">Enc. Finanças</option>
                <option value="Chefe_Departamento">Chefe de Departamento</option>
                <option value="Vice_Diretor">Vice-Diretor</option>
                <option value="Diretor">Diretor</option>
            </select>
            <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px;">Salvar</button>
        </form>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                <th style="padding: 10px;">Nome</th>
                <th style="padding: 10px;">Login</th>
                <th style="padding: 10px;">Perfil</th>
                <th style="padding: 10px;">Unidade (OMAP)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dados['users'] as $u): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><?= htmlspecialchars($u['name']) ?></td>
                <td style="padding: 10px;"><b><?= htmlspecialchars($u['username']) ?></b></td>
                <td style="padding: 10px;"><span style="background:#004488; color:white; padding:4px 8px; border-radius:4px; font-size:0.85em;"><?= htmlspecialchars($u['role']) ?></span></td>
                <td style="padding: 10px;"><?= htmlspecialchars($u['unit_omap'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>