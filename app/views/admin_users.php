<?php
$page_title = 'Gestão de Usuários - SIGEF';
require __DIR__ . '/partials/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">🛡️ Gestão de Acessos (Cadastro)</h2>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #28a745; margin-bottom: 25px;">
    <h3 style="margin-top: 0; color: #333;">➕ Cadastrar Novo Militar</h3>
    <form action="/admin/users" method="POST" style="display: flex; gap: 15px; flex-wrap: wrap;">
        
        <input type="text" name="name" placeholder="Nome Completo / Guerra" required style="flex: 1; min-width: 200px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" name="username" placeholder="NIP (Utilizador)" required style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="password" name="password" placeholder="Senha Inicial" required style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        
        <select name="role" required style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="">-- Selecione o Perfil --</option>
            <option value="Operador">Operador (Lançador)</option>
            <option value="Protocolo">Setor de Protocolo</option>
            <option value="Enc_Financas">Encarregado de Finanças</option>
            <option value="Admin">Administrador</option>
        </select>

        <select name="origem_setor" required style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="">-- Setor / Origem --</option>
            <option value="BAMRJ">BAMRJ (Interno)</option>
            <option value="OMAP">OMAP (Pagamento PA)</option>
        </select>
        
        <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px;">Salvar</button>
    </form>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">👥 Utilizadores Cadastrados</h3>
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding: 12px; color: #002244;">Nome</th>
                    <th style="padding: 12px; color: #002244;">NIP / Login</th>
                    <th style="padding: 12px; color: #002244;">Perfil</th>
                    <th style="padding: 12px; color: #002244;">Setor (Origem)</th>
                    <th style="padding: 12px; text-align: center; color: #002244;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><?= htmlspecialchars($u['name']) ?></td>
                    <td style="padding: 12px;"><b><?= htmlspecialchars($u['username']) ?></b></td>
                    <td style="padding: 12px;"><span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td style="padding: 12px;"><strong><?= htmlspecialchars($u['origem_setor']) ?></strong></td>
                    <td style="padding: 12px; text-align: center;">
                        <?php if ($u['username'] !== 'admin'): ?>
                            <a href="/admin/delete_user?id=<?= $u['id'] ?>" style="color: #dc3545; text-decoration: none; font-weight: bold; font-size: 0.9em;" onclick="return confirm('Tem certeza que deseja remover este utilizador?')">❌ Excluir</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>