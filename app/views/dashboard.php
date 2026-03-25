<?php
$page_title = 'Dashboard - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';
$role = $_SESSION['role'];
$atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
$is_search = isset($_GET['q']) && !empty($_GET['q']);

// 🛡️ Roteamento Inteligente do Radar
$link_inbox = '/';
if ($role === 'Operador') $link_inbox = '/operador/fila';
if ($role === 'Protocolo') $link_inbox = '/protocolo/fila';
if (in_array($role, ['Gestor_Financeiro', 'Gestor_Substituto', 'Chefe_Departamento', 'Agente_Fiscal', 'Ordenador_Despesas'])) $link_inbox = '/assinador/fila';
?>

<a href="<?= $link_inbox ?>" id="alerta-novo-doc" style="display: none; background: #dc3545; color: white; padding: 12px 20px; text-align: center; font-weight: bold; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); text-decoration: none; border: 2px solid #a71d2a;">
    🚨 ATENÇÃO! VOCÊ TEM <span id="radar-count" style="font-size: 1.2em; background: white; color: #dc3545; padding: 2px 8px; border-radius: 50%; margin: 0 5px;">0</span> ITEM(NS) AGUARDANDO SUA AÇÃO! CLIQUE AQUI.
</a>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; border-left: 5px solid #004488;">
    <div>
        <h3 style="margin: 0; color: #002244;">Painel Principal - Perfil: <span style="color: #666;"><?= htmlspecialchars(str_replace('_', ' ', $role)) ?></span></h3>
        <p style="margin: 5px 0 0 0; color: #555; font-size: 0.9em;">Setor Operacional: <b><?= htmlspecialchars($_SESSION['origem_setor']) ?></b></p>
    </div>
    
    <?php if ($role !== 'Admin'): ?>
    <form action="/" method="GET" style="display: flex; gap: 5px;">
        <select name="ano" onchange="this.form.submit()" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-weight: bold; background: #f8f9fa;">
            <?php $ano_atual = date('Y'); for($i = max($ano_atual, 2024); $i >= 2024; $i--) { echo "<option value='$i' ".(($_GET['ano']??$ano_atual)==$i?'selected':'').">$i</option>"; } ?>
        </select>
        <input type="text" name="q" placeholder="Buscar Global (ID/DE/CNPJ)..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="padding: 10px; border: 1px solid #ccc; width: 250px; border-radius: 4px; font-weight: bold;">
        <button type="submit" class="btn btn-primary" style="padding: 10px 15px;">🔍 Pesquisar</button>
    </form>
    <?php endif; ?>
</div>

<?php if (in_array($role, ['Chefe_Departamento', 'Agente_Fiscal', 'Gestor_Financeiro']) && !$is_search): ?>
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <b style="color: #856404; font-size: 1.1em;">🔄 Modo de Operação (Substituição Hierárquica):</b>
        <form action="/assinador/toggleSubstituto" method="POST" style="margin:0; display:flex; gap:10px;">
            <?php if ($atuando_substituto): ?>
                <button type="submit" class="btn btn-danger" style="font-weight: bold; box-shadow: 0 0 8px rgba(220,53,69,0.5);">⚠️ Atuando como Substituto (Desativar)</button>
            <?php else: ?>
                <button type="submit" class="btn btn-outline-secondary" style="font-weight: bold; background: #e9ecef; color: #333; border: 1px solid #ccc;">Habilitar Modo Substituto</button>
            <?php endif; ?>
        </form>
    </div>
<?php endif; ?>

<?php if ($role === 'Admin'): ?>
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="color: #002244;">Área Restrita Administrativa</h2>
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <a href="/admin/users" class="btn btn-success" style="padding: 12px 25px; font-size: 1.1em;">⚙️ Gestão de Usuários e Sistema</a>
        </div>
    </div>
<?php else: ?>
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/" class="btn" style="background: #e2e3e5; color: #002244; border: 1px solid #ccc;">🏠 Dashboard / Início</a>

        <?php if ($role === 'Protocolo' || $role === 'Operador'): ?>
            <a href="/protocolo/fila" class="btn btn-info">📥 Fila do Protocolo</a>
        <?php endif; ?>
        
        <?php if ($role === 'Operador'): ?>
            <a href="/operador/fila" class="btn btn-warning">⚙️ Fila de Execução Financeira</a>
            <a href="/operador/monitoramento" class="btn" style="background: #343a40; color: white;">📊 Monitoramento Global</a>
            <a href="/relatorio/ob" class="btn" style="background: #6f42c1; color: white;">📊 Relatório de OBs Liquidadas</a>
        <?php endif; ?>
        
        <?php if (!in_array($role, ['Protocolo', 'Ordenador_Despesas', 'Agente_Fiscal', 'Chefe_Departamento', 'Gestor_Financeiro', 'Gestor_Substituto'])): ?>
            <a href="/de/nova" class="btn btn-success">➕ Lançar Nova DE</a>
        <?php endif; ?>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0; color: #002244; border-bottom: 2px solid #eee; padding-bottom: 10px;"><?= $is_search ? "🔍 Resultados Globais" : "📥 Lotes Movimentados (Base de Consulta)" ?></h3>
        
        <?php if (empty($lotes)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">Nenhum documento encontrado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                        <th style="padding: 12px;">DE (Lote Físico)</th>
                        <th style="padding: 12px;">Origem</th>
                        <th style="padding: 12px;">Data</th>
                        <th style="padding: 12px; text-align: right;">Ações / Auditoria</th>
                    </tr>
                    <?php foreach ($lotes as $lote): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <code style="color: #d32f2f; font-weight: bold; font-size: 1.1em;"><?= htmlspecialchars($lote['numero_geral']) ?></code>
                            
                            <?php if (($lote['qtd_rejeitados'] ?? 0) > 0 && !$is_search): ?>
                                <br><span style="display:inline-block; margin-top:5px; background: #dc3545; color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">🚨 CONTÉM ITENS REJEITADOS</span>
                            <?php endif; ?>
                            
                            <?php if ($is_search): ?>
                                <br><small style="color:#004488; font-weight:bold;"><?= str_replace('_', ' ', htmlspecialchars($lote['status_inbox'])) ?></small>
                            <?php else: ?>
                                <?php 
                                    $status_representado = $lote['status_inbox'] ?? '';
                                    if ($role === 'Chefe_Departamento' && $status_representado === 'AGU_VRF_VICE_DIRETOR') echo "<br><span style='background: #e83e8c; color: white; padding: 3px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold;'>Assinando como Agente Fiscal</span>";
                                    elseif ($role === 'Agente_Fiscal' && $status_representado === 'AGU_ASS_DIRETOR') echo "<br><span style='background: #e83e8c; color: white; padding: 3px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold;'>Assinando como Ordenador de Despesas</span>";
                                ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;"><b><?= htmlspecialchars($lote['origem_tipo']) ?></b></td>
                        <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="/de/acompanhar?id=<?= $lote['id'] ?>" class="btn btn-info" style="padding: 6px 12px; font-size: 0.9em;">🔍 Rastreador de Itens (ID)</a>
                            
                            <?php if (!$is_search && $role === 'Protocolo'): ?>
                                <a href="/protocolo/lote?id=<?= $lote['id'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.9em; margin-left: 5px;">📂 Processar Protocolo</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
if ("<?= $role ?>" !== 'Admin') {
    function checkRadar() {
        fetch('/api/check_inbox?t=' + new Date().getTime()).then(res => res.json()).then(data => {
            const alerta = document.getElementById('alerta-novo-doc'); const badge = document.getElementById('radar-count');
            if (data.count > 0) { badge.innerText = data.count; alerta.style.display = 'block'; } else { alerta.style.display = 'none'; }
        });
    }
    setInterval(checkRadar, 15000); checkRadar(); 
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
