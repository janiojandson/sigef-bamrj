<?php $page_title = 'Gestão de Usuários - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">🛡️ Gestão de Acessos (Cadastro)</h2>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #28a745; margin-bottom: 25px;">
    <h3 style="margin-top: 0; color: #333;">➕ Cadastrar Novo Militar</h3>
    <form action="/admin/users" method="POST" style="display: flex; gap: 15px; flex-wrap: wrap;">
        
        <input type="text" name="name" placeholder="Nome / Guerra" required style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" name="username" placeholder="NIP (Login)" required style="flex: 1; min-width: 120px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="password" name="password" placeholder="Senha Inicial" required style="flex: 1; min-width: 120px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        
        <select name="role" id="select-role" required onchange="verificarOmap()" style="flex: 1; min-width: 180px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="">-- Selecione o Perfil --</option>
            <option value="Operador">Operador (Execução Fin.)</option>
            <option value="Setor_BAMRJ">Setor BAMRJ (Lançador Interno)</option>
            <option value="Protocolo">Setor de Protocolo</option>
            <option value="OMAP">OMAP (Origem)</option>
            <option value="Ajudante_Encarregado">Ajudante do Encarregado</option>
            <option value="Enc_Financas">Encarregado de Finanças</option>
            <option value="Chefe_Departamento">Chefe de Departamento</option>
            <option value="Vice_Diretor">Vice-Diretor</option>
            <option value="Diretor">Diretor</option>
            <option value="Admin">Administrador (TI)</option>
        </select>

        <input type="text" name="omap_sigla" id="input-omap" placeholder="Ex: CCSM, DGN..." style="display: none; flex: 1; min-width: 100px; padding: 10px; border: 1px solid #ffcc00; background: #fffde7; border-radius: 4px; font-weight: bold;">
        
        <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px;">Salvar</button>
    </form>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
        <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
            <th style="padding: 12px;">Nome</th><th style="padding: 12px;">NIP</th><th style="padding: 12px;">Perfil</th><th style="padding: 12px;">Origem (Setor)</th><th style="padding: 12px; text-align: center;">Ações</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 12px;"><?= htmlspecialchars($u['name']) ?></td>
            <td style="padding: 12px;"><b><?= htmlspecialchars($u['username']) ?></b></td>
            <td style="padding: 12px;"><span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;"><?= htmlspecialchars($u['role']) ?></span></td>
            <td style="padding: 12px;"><strong><?= htmlspecialchars($u['origem_setor']) ?></strong></td>
            <td style="padding: 12px; text-align: center;">
                <?php if ($u['username'] !== 'admin'): ?>
                    <a href="/admin/delete_user?id=<?= $u['id'] ?>" style="color: #dc3545; text-decoration: none; font-weight: bold;" onclick="return confirm('Remover utilizador?')">❌ Excluir</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function verificarOmap() {
    var role = document.getElementById('select-role').value;
    var inputOmap = document.getElementById('input-omap');
    if (role === 'OMAP') {
        inputOmap.style.display = 'block';
        inputOmap.setAttribute('required', 'required');
    } else {
        inputOmap.style.display = 'none';
        inputOmap.removeAttribute('required');
        inputOmap.value = '';
    }
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>