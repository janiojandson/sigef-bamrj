<?php
$page_title = 'Dashboard - Assinador BAMRJ';
require __DIR__ . '/partials/header.php';

$dashController = new \App\Controllers\DashboardController();
$dados = $dashController->getDashboardData();

$role = $dados['role'];
$is_substitute = $dados['is_substitute'];
$users = $dados['users'];
$documents = $dados['documents'];
$pre_protocol = $dados['pre_protocol'];
$inbox_count = $dados['inbox_count'];
?>

<style>
/* 🌟 ALERTA VISUAL SUTIL E EDUCADO */
@keyframes pulso-suave {
    0%   { background-color: #ffcc00; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    50%  { background-color: #ffe066; box-shadow: 0 4px 15px rgba(255, 204, 0, 0.6); }
    100% { background-color: #ffcc00; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
}
.alerta-piscando {
    display: block !important;
    animation: pulso-suave 2.5s infinite ease-in-out !important;
    border: 1px solid #e6b800 !important;
}
</style>

<div id="alerta-novo-doc" style="display: none; background: #ffcc00; color: #002244; padding: 12px; text-align: center; font-weight: bold; margin-bottom: 20px; border-radius: 5px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" onclick="location.reload()">
    🔔 Há novos movimentos na sua caixa de entrada. Clique para atualizar.
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #004488; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="margin: 0; color: #002244;">Patente/Perfil: <span style="color: #666;"><?= $role === 'Enc_Financas' ? 'Enc. Finanças' : htmlspecialchars($role) ?></span></h3>
        <?php if ($is_substitute): ?>
            <span style="background: #ffcc00; color: black; padding: 2px 8px; border-radius: 3px; font-weight: bold; font-size: 0.8em; display: inline-block; margin-top: 5px;">MODO SUBSTITUTO ATIVO</span>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 10px; width: 100%; justify-content: flex-end;">
        <?php if (in_array($role, ['Chefe_Departamento', 'Vice_Diretor'])): ?>
            <a href="/toggle_substitute" style="background: #ffcc00; color: #002244; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; text-align: center;">
                <?= $is_substitute ? '⬅️ Desativar Substituição' : '⚡ Ativar Substituição Superior' ?>
            </a>
        <?php endif; ?>
        
        <form action="/" method="GET" style="display: flex; gap: 5px;">
            <select name="ano" id="filtro-ano-dash" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-weight: bold; color: #002244;"></select>
            <input type="text" name="q" placeholder="Buscar SOLEMP, CNPJ, Nome..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="padding: 10px; border: 1px solid #ccc; width: 250px; border-radius: 4px;">
            <button type="submit" style="padding: 10px 15px; background: #004488; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🔍</button>
        </form>
    </div>
</div>

<?php if ($role === 'Admin'): ?>
<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #dc3545; margin-bottom: 20px;">
    <h3 style="margin-top: 0; color: #002244;">🛡️ Gestão de Utilizadores</h3>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ddd;">
        <h4 style="margin-top:0; color: #333;">➕ Cadastrar Novo Utilizador</h4>
        <form action="/admin/create_user" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="name" placeholder="Nome Completo" required style="padding: 10px; flex: 1.5; border: 1px solid #ccc; border-radius: 4px;">
            <input type="text" name="username" placeholder="NIP / Login" required style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
            <input type="password" name="password" placeholder="Senha Inicial" required style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
            <select name="role" required style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
                <option value="Operador">Operador</option>
                <option value="Enc_Financas">Enc. Finanças</option>
                <option value="Ajudante_Encarregado">Ajudante do Encarregado</option>
                <option value="Chefe_Departamento">Chefe de Departamento</option>
                <option value="Vice_Diretor">Vice-Diretor</option>
                <option value="Diretor">Diretor</option>
                <option value="Usuário Comum">Usuário Comum</option>
            </select>
            <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px;">Salvar</button>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
            <thead><tr style="text-align: left; background: #f8f9fa; border-bottom: 2px solid #ddd;"><th style="padding: 10px;">Nome</th><th style="padding: 10px;">Utilizador</th><th style="padding: 10px;">Perfil</th><th style="padding: 10px;">Ações</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= htmlspecialchars($u['name']) ?></td>
                    <td style="padding: 10px;"><b><?= htmlspecialchars($u['username']) ?></b></td>
                    <td style="padding: 10px;">
                        <form action="/admin/edit_user" method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role" onchange="this.form.submit()" style="padding: 5px; border-radius: 3px;">
                                <option value="Admin" <?= $u['role'] == 'Admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="Operador" <?= $u['role'] == 'Operador' ? 'selected' : '' ?>>Operador</option>
                                <option value="Enc_Financas" <?= $u['role'] == 'Enc_Financas' ? 'selected' : '' ?>>Enc. Finanças</option>
                                <option value="Ajudante_Encarregado" <?= $u['role'] == 'Ajudante_Encarregado' ? 'selected' : '' ?>>Ajudante</option>
                                <option value="Chefe_Departamento" <?= $u['role'] == 'Chefe_Departamento' ? 'selected' : '' ?>>Chefe Depto</option>
                                <option value="Vice_Diretor" <?= $u['role'] == 'Vice_Diretor' ? 'selected' : '' ?>>Vice-Diretor</option>
                                <option value="Diretor" <?= $u['role'] == 'Diretor' ? 'selected' : '' ?>>Diretor</option>
                                <option value="Usuário Comum" <?= $u['role'] == 'Usuário Comum' ? 'selected' : '' ?>>Usuário Comum</option>
                            </select>
                        </form>
                    </td>
                    <td style="padding: 10px;">
                        <?php if ($u['username'] !== 'admin'): ?>
                            <a href="/admin/delete?id=<?= $u['id'] ?>" style="color: #dc3545; text-decoration: none; font-weight: bold;" onclick="return confirm('Excluir utilizador?')">❌ Excluir</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'Operador'): ?>
<div style="display: flex; gap: 10px; margin-bottom: 20px;">
    <button onclick="document.getElementById('modal').style.display='block'" style="background: #28a745; color: white; padding: 12px 20px; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">➕ Iniciar Novo Processo</button>
    <a href="/arquivo" style="background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">🗄️ Acessar Arquivo Geral</a>
</div>

<div id="modal" style="display: none; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-left: 10px solid #28a745; margin-bottom: 20px;">
    <h3 style="margin-top:0; color: #002244;">📄 Abertura de Demanda</h3>
    <form action="/upload" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="protocol" value="<?= $pre_protocol ?>">
        <p style="background: #e9ecef; padding: 10px; border-radius: 5px; border-left: 4px solid #6c757d;"><strong>Protocolo Gerado:</strong> <code style="font-size: 1.1em; color: #d32f2f;"><?= $pre_protocol ?></code></p>
        
        <input type="text" name="process_name" placeholder="Assunto do Processo" required style="width: 100%; padding: 10px; margin-bottom: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
        
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" name="cpf_cnpj" placeholder="CPF ou CNPJ (Opcional)" style="flex: 1; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
            <input type="text" name="solemp" placeholder="Nº da SOLEMP (Opcional)" style="flex: 1; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <label style="display: inline-block; margin-bottom: 15px; background: #fff3cd; padding: 8px 12px; border-radius: 4px; border: 1px solid #ffeeba; cursor: pointer;"><input type="checkbox" name="priority" value="1"> 🚩 Marcar como Processo Prioritário</label>
        
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px dashed #ccc;">
                <label><b>Minutas (PDF):</b></label><br>
                <input type="file" id="m-in" accept="application/pdf" multiple style="margin-top:10px; width: 100%;">
                <ul id="m-list" style="font-size: 0.85em; color: #666; padding-left: 20px; margin-top: 10px;"></ul>
                <input type="file" name="minutas[]" id="m-hidden" multiple style="display: none;">
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px dashed #ccc;">
                <label><b>Anexos Diversos (PDF):</b></label><br>
                <input type="file" id="a-in" accept="application/pdf" multiple style="margin-top:10px; width: 100%;">
                <ul id="a-list" style="font-size: 0.85em; color: #666; padding-left: 20px; margin-top: 10px;"></ul>
                <input type="file" name="anexos[]" id="a-hidden" multiple style="display: none;">
            </div>
        </div>
        <textarea name="observation" placeholder="Despacho inicial ou observações..." style="width: 100%; height: 80px; margin-bottom: 15px; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;"></textarea>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" style="background: #004488; color: white; padding: 12px 25px; border: none; cursor: pointer; font-weight: bold; flex: 2; border-radius: 4px;">Gerar e Tramitar</button>
            <button type="button" onclick="document.getElementById('modal').style.display='none'" style="background: #dc3545; color: white; border: none; padding: 12px 20px; cursor: pointer; flex: 1; border-radius: 4px; font-weight: bold;">Cancelar</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($role !== 'Admin'): ?>
<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <?php if (empty($documents)): ?>
        <h3 style="text-align: center; color: #666; padding: 40px 0;">Nenhum processo pendente na sua caixa de entrada.</h3>
    <?php else: ?>
        <h3 style="margin-top: 0; color: #002244; border-bottom: 2px solid #eee; padding-bottom: 10px;">📂 Processos Encontrados / Caixa de Entrada</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px; margin-top: 10px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                        <th style="padding: 12px;">Prior.</th>
                        <th style="padding: 12px;">Protocolo</th>
                        <th style="padding: 12px;">Assunto</th>
                        <th style="padding: 12px;">Status</th>
                        <th style="padding: 12px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr style="border-bottom: 1px solid #eee; <?= $doc['is_priority'] ? 'background: #fff5f5;' : '' ?> transition: 0.2s;">
                        <td style="padding: 12px; text-align: center;"><?= $doc['is_priority'] ? '🚩' : '🏳️' ?></td>
                        <td style="padding: 12px;"><code style="color: #d32f2f; font-weight: bold;"><?= htmlspecialchars($doc['protocol']) ?></code></td>
                        <td style="padding: 12px;"><b><?= htmlspecialchars($doc['name']) ?></b><br><small style="color: #666;">SOLEMP: <?= htmlspecialchars($doc['solemp']) ?: '-' ?></small></td>
                        <td style="padding: 12px;">
                            <?php
                            $statusBg = '#e2e3e5'; $statusColor = '#383d41';
                            if ($doc['status'] === 'Devolvido - Operador') { $statusBg = '#f8d7da'; $statusColor = '#721c24'; }
                            elseif ($doc['status'] === 'Aguardando Empenho - Operador') { $statusBg = '#d4edda'; $statusColor = '#155724'; }
                            ?>
                            <span style="font-size: 0.85em; padding: 6px 10px; border-radius: 4px; font-weight: bold; background: <?= $statusBg ?>; color: <?= $statusColor ?>;">
                                <?= htmlspecialchars($doc['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; display: flex; gap: 8px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                            
                            <a href="/view?id=<?= $doc['id'] ?>" style="background: #004488; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9em;">Abrir</a>
                            
                            <?php if ($role === 'Operador'): ?>
                                <?php if ($doc['status'] === 'Devolvido - Operador'): ?>
                                    <a href="/edit?id=<?= $doc['id'] ?>" style="background: #ffcc00; color: #002244; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9em;">✏️ Corrigir</a>
                                <?php endif; ?>

                                <form action="/cancel?id=<?= $doc['id'] ?>" method="POST" onsubmit="return confirm('Deseja realmente CANCELAR este processo permanentemente?');" style="margin: 0;">
                                    <button type="submit" style="background: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em; font-weight: bold;">Cancelar</button>
                                </form>
                                
                                <?php if ($doc['status'] === 'Aguardando Empenho - Operador'): ?>
                                    <form action="/upload_ne?id=<?= $doc['id'] ?>" method="POST" enctype="multipart/form-data" style="margin: 0; display: flex; gap: 5px; align-items: center; background: #e9ecef; padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                        <select name="final_status" required style="padding: 6px; font-size: 0.85em; border-radius: 3px; border: 1px solid #ccc;">
                                            <option value="Arquivado">Arquivar</option>
                                            <option value="Reforçado">Reforçado</option>
                                            <option value="Anulado">Anulado</option>
                                        </select>
                                        <input type="file" id="ne-in-<?= $doc['id'] ?>" name="nota_empenho" required accept="application/pdf" style="display: none;" onchange="document.getElementById('ne-btn-<?= $doc['id'] ?>').style.display='block'">
                                        <button type="button" onclick="document.getElementById('ne-in-<?= $doc['id'] ?>').click()" style="background: #6c757d; color: white; padding: 6px 12px; border-radius: 3px; border: none; cursor: pointer; font-size: 0.85em; font-weight: bold;">📎 Anexar NE</button>
                                        <button type="submit" id="ne-btn-<?= $doc['id'] ?>" style="background: #28a745; color: white; padding: 6px 15px; border: none; border-radius: 3px; cursor: pointer; font-size: 0.9em; font-weight: bold; display: none;">Salvar NE</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?> 

<script>
const selectAno = document.getElementById('filtro-ano-dash');
const anoAtual = new Date().getFullYear();
const urlParams = new URLSearchParams(window.location.search);
const anoPesq = urlParams.get('ano');

for (let ano = 2026; ano <= Math.max(2026, anoAtual); ano++) {
    let opt = document.createElement('option');
    opt.value = ano; opt.innerHTML = ano;
    if (anoPesq && parseInt(anoPesq) === ano) opt.selected = true;
    else if (!anoPesq && ano === anoAtual) opt.selected = true;
    selectAno.appendChild(opt);
}

const dtM = new DataTransfer(), dtA = new DataTransfer();
function setupFiles(inId, hidId, listId, dt) {
    const inp = document.getElementById(inId), hid = document.getElementById(hidId), list = document.getElementById(listId);
    if(!inp) return;
    inp.addEventListener('change', () => { for(let f of inp.files) dt.items.add(f); renderFiles(list, hid, dt); });
}
function renderFiles(list, hid, dt) {
    list.innerHTML = ''; hid.files = dt.files;
    Array.from(dt.files).forEach((f, i) => {
        const li = document.createElement('li');
        li.innerHTML = f.name + ' <b style="cursor:pointer;color:#dc3545;margin-left:10px">[X]</b>';
        li.querySelector('b').onclick = () => { dt.items.remove(i); renderFiles(list, hid, dt); };
        list.appendChild(li);
    });
}
setupFiles('m-in', 'm-hidden', 'm-list', dtM);
setupFiles('a-in', 'a-hidden', 'a-list', dtA);

let currentInboxCount = <?= $inbox_count ?>;
setInterval(function() {
    fetch('/api/check_inbox?t=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            if (data.count !== currentInboxCount) {
                const alerta = document.getElementById('alerta-novo-doc');
                alerta.style.display = 'block';
                alerta.classList.add('alerta-piscando');
            }
        })
        .catch(err => console.error("Falha no radar:", err));
}, 60000); 
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>