<?php $page_title = 'Gestão de Sistema - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #00447c;">🛡️ Administração do Sistema (SIGEF)</h2>
    <a href="/" class="btn btn-secondary">⬅️ Voltar ao Início</a>
</div>

<section style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-top: 4px solid #00447c;">
    <h3 style="color: #00447c; margin-top: 0;">➕ Cadastrar Novo Militar</h3>
    <form action="/admin/users" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
        <input type="hidden" name="action" value="create">
        
        <div>
            <label style="font-size: 0.85em; color: #555; font-weight: bold;">Pos/Gra Nome de Guerra</label>
            <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div>
            <label style="font-size: 0.85em; color: #555; font-weight: bold;">Utilizador</label>
            <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div>
            <label style="font-size: 0.85em; color: #555; font-weight: bold;">Senha Inicial</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        
        <div>
            <label style="font-size: 0.85em; color: #555; font-weight: bold;">Perfil no SIGEF</label>
            <select name="role" required id="select-role" onchange="verificarOmap()" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="Operador">Operador (Execução Fin.)</option>
                <option value="Protocolo">Setor de Protocolo</option>
                <option value="Setor_BAMRJ">Setor BAMRJ (Lançador)</option>
                <option value="OMAP">OMAP (Origem Externa)</option>
                <option value="Gestor_Financeiro">Gestor Financeiro</option>
                <option value="Gestor_Substituto">Gestor Fin. Substituto</option>
                <option value="Chefe_Departamento">Chefe de Departamento</option>
                <option value="Agente_Fiscal">Agente Fiscal (Vice-Diretor)</option>
                <option value="Ordenador_Despesas">Ordenador de Despesas (Diretor)</option>
                <option value="Admin">Administrador (TI)</option>
            </select>
        </div>

        <div id="div-omap" style="display: none;">
            <label style="font-size: 0.85em; color: #555; font-weight: bold;">Sigla da OMAP</label>
            <input type="text" name="omap_sigla" id="input-omap" placeholder="Ex: CCSM..." style="width: 100%; padding: 10px; border: 1px solid #ffcc00; background: #fffde7; border-radius: 4px;">
        </div>
        
        <button type="submit" class="btn btn-success" style="height: 42px;">CADASTRAR</button>
    </form>
</section>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Post/Grad Nome de Guerra</th>
                <th>Utilizador</th>
                <th>Origem (Setor)</th>
                <th>Edição Rápida de Perfil / Senha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td style="font-weight: bold;"><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><strong><?= htmlspecialchars($u['origem_setor']) ?></strong></td>
                <td>
                    <form method="POST" action="/admin/users" style="display: flex; gap: 5px; align-items: center; margin: 0;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="role" style="padding: 6px; border-radius: 3px; border: 1px solid #ccc; max-width: 150px;">
                            <option value="<?= $u['role'] ?>"><?= $u['role'] ?> (Atual)</option>
                            <option value="Operador">Operador</option>
                            <option value="Protocolo">Protocolo</option>
                            <option value="OMAP">OMAP</option>
                            <option value="Gestor_Financeiro">Gestor Financeiro</option>
                            <option value="Gestor_Substituto">Gestor Substituto</option>
                            <option value="Chefe_Departamento">Chefe Depto</option>
                            <option value="Agente_Fiscal">Agente Fiscal</option>
                            <option value="Ordenador_Despesas">Ordenador Desp.</option>
                            <option value="Admin">Admin</option>
                        </select>
                        <input type="password" name="password" placeholder="Nova Senha..." style="width: 120px; padding: 6px; border-radius: 3px; border: 1px solid #ccc;">
                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px;">Salvar</button>
                        <?php if($u['username'] !== 'admin'): ?>
                            <a href="/admin/delete_user?id=<?= $u['id'] ?>" class="btn btn-danger" style="padding: 6px 12px;" onclick="return confirm('Excluir militar do sistema?')">Excluir</a>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<section style="background: #fff5f5; padding: 25px; border-radius: 8px; margin-top: 40px; border: 2px solid #dc3545; box-shadow: 0 4px 10px rgba(220,53,69,0.2);">
    <h3 style="color: #dc3545; margin-top: 0;">⚠️ ZONA DE PERIGO (Ferramentas de Manutenção Web)</h3>
    <p style="color: #666; font-size: 0.95em; margin-bottom: 20px;">Utilize estas ferramentas exclusivamente durante a fase de testes ou manutenção estrutural. As ações são irreversíveis.</p>
    
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div style="flex: 1; background: white; padding: 20px; border-radius: 6px; border: 1px solid #17a2b8;">
            <h4 style="margin-top: 0; color: #0c5460;">🔄 Patch SQL (Ajustar Nomenclaturas)</h4>
            <p style="font-size: 0.85em; color: #555; margin-bottom: 20px;">Renomeia a coluna `PA` para `NS` e converte automaticamente os perfis antigos (Enc. Finanças -> Gestor Financeiro, etc).</p>
            <form action="/admin/users" method="POST" onsubmit="return confirm('Deseja aplicar a correção no banco de dados agora?');">
                <input type="hidden" name="action" value="migrate_db">
                <button type="submit" class="btn btn-info" style="width: 100%;">Aplicar Patch SQL</button>
            </form>
        </div>

        <div style="flex: 1; background: white; padding: 20px; border-radius: 6px; border: 1px solid #ffc107;">
            <h4 style="margin-top: 0; color: #856404;">🧹 Limpar Processos (Wipe Dados)</h4>
            <p style="font-size: 0.85em; color: #555; margin-bottom: 20px;">Apaga todas as DEs, Históricos, RAPs e Notas do banco de testes. <strong>Os utilizadores cadastrados são mantidos intactos.</strong></p>
            <form action="/admin/users" method="POST" onsubmit="return confirm('ATENÇÃO: Limpar todos os processos?');">
                <input type="hidden" name="action" value="wipe_data">
                <button type="submit" class="btn btn-warning" style="width: 100%;">Executar Limpeza</button>
            </form>
        </div>

        <div style="flex: 1; background: white; padding: 20px; border-radius: 6px; border: 1px solid #dc3545;">
            <h4 style="margin-top: 0; color: #721c24;">☢️ Reset Total (Factory Reset)</h4>
            <p style="font-size: 0.85em; color: #555; margin-bottom: 20px;">Destrói todas as tabelas e recria a estrutura limpa do SIGEF. Apenas o utilizador <strong>admin</strong> vai sobrar no sistema.</p>
            <form action="/admin/users" method="POST" onsubmit="return confirm('ALERTA MÁXIMO MILITAR: Isso fará o DROP TABLE de todo o SIGEF e apagará os usuários. Tem certeza?');">
                <input type="hidden" name="action" value="factory_reset">
                <button type="submit" class="btn btn-danger" style="width: 100%;">Executar Formatação Total</button>
            </form>
        </div>
    </div>
</section>

<script>
function verificarOmap() {
    var role = document.getElementById('select-role').value;
    var divOmap = document.getElementById('div-omap');
    var inputOmap = document.getElementById('input-omap');
    if (role === 'OMAP') { 
        divOmap.style.display = 'block'; 
        inputOmap.setAttribute('required', 'required'); 
    } else { 
        divOmap.style.display = 'none'; 
        inputOmap.removeAttribute('required'); 
    }
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
