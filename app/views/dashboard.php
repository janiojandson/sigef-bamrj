<?php
$page_title = 'Dashboard - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';
$role = $_SESSION['role'];
$atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
$is_search = isset($_GET['q']) && !empty($_GET['q']);

$link_inbox = '/';
if ($role === 'Operador') $link_inbox = '/operador/fila';
if ($role === 'Protocolo') $link_inbox = '/protocolo/fila';
?>

<a href="<?= $link_inbox ?>" id="alerta-novo-doc" style="display: none; background: #dc3545; color: white; padding: 12px 20px; text-align: center; font-weight: bold; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); text-decoration: none; border: 2px solid #a71d2a;">
    🔔 VOCÊ TEM <span id="radar-count" style="font-size: 1.2em; background: white; color: #dc3545; padding: 2px 8px; border-radius: 50%; margin: 0 5px;">0</span> PENDÊNCIA(S) AGUARDANDO SUA AÇÃO! CLIQUE AQUI.
</a>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; border-left: 5px solid #004488;">
    <div>
        <h3 style="margin: 0; color: #002244;">Painel Principal - Perfil: <span style="color: #666;"><?= htmlspecialchars($role) ?></span></h3>
        <p style="margin: 5px 0 0 0; color: #555; font-size: 0.9em;">Setor Operacional: <b><?= htmlspecialchars($_SESSION['origem_setor']) ?></b></p>
    </div>
    
    <?php if ($role !== 'Admin'): ?>
    <form action="/" method="GET" style="display: flex; gap: 5px;">
        <select name="ano" onchange="this.form.submit()" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-weight: bold; background: #f8f9fa;">
            <?php 
                $ano_atual = date('Y');
                for($i = $ano_atual; $i >= 2024; $i--) {
                    $selected = (isset($_GET['ano']) && $_GET['ano'] == $i) || (!isset($_GET['ano']) && $i == $ano_atual) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
            ?>
        </select>
        <input type="text" name="q" placeholder="Buscar Global (DE/CNPJ)..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="padding: 10px; border: 1px solid #ccc; width: 250px; border-radius: 4px; font-weight: bold;">
        <button type="submit" style="padding: 10px 15px; background: #004488; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🔍 Pesquisar</button>
    </form>
    <?php endif; ?>
</div>

<?php if (in_array($role, ['Chefe_Departamento', 'Vice_Diretor']) && !$is_search): ?>
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <b style="color: #856404; font-size: 1.1em;">🔄 Modo de Operação (Substituição Hierárquica):</b>
        <div style="display: flex; gap: 10px;">
            <a href="/?substituto=0" style="padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; <?= !$atuando_substituto ? 'background: #28a745; color: white;' : 'background: #e9ecef; color: #333; border: 1px solid #ccc;' ?>">Assinar como <?= htmlspecialchars($role) ?></a>
            <a href="/?substituto=1" style="padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; <?= $atuando_substituto ? 'background: #dc3545; color: white;' : 'background: #e9ecef; color: #333; border: 1px solid #ccc;' ?>">Atuar como Substituto (Fila Superior)</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($role === 'Admin'): ?>
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="color: #002244;">Área Restrita Administrativa</h2>
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <a href="/admin/users" style="background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 1.1em;">⚙️ Gestão de Usuários</a>
            <a href="/admin/limpar_dados" onclick="return confirm('ATENÇÃO: ISSO APAGARÁ TODAS AS DEs DO BANCO. Deseja prosseguir?')" style="background: #dc3545; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 1.1em;">💣 Zerar Base de Testes</a>
        </div>
    </div>

<?php else: ?>
    
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <?php if ($role === 'Protocolo' || $role === 'Operador'): ?>
            <a href="/protocolo/fila" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">📥 Fila do Protocolo</a>
        <?php endif; ?>

        <?php if ($role === 'Operador'): ?>
            <a href="/operador/fila" style="background: #ffcc00; color: #002244; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">⚙️ Fila de Execução (NP/LF/OB)</a>
            <a href="/operador/monitoramento" style="background: #343a40; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">📊 Monitoramento Global</a>
        <?php endif; ?>
        
        <?php if (in_array($role, ['Operador', 'Diretor', 'Vice_Diretor', 'Enc_Financas'])): ?>
            <a href="/relatorio/ob" style="background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">📊 Relatório de OBs Liquidadas</a>
        <?php endif; ?>

        <?php if (!in_array($role, ['Protocolo', 'Diretor', 'Vice_Diretor', 'Chefe_Departamento', 'Enc_Financas', 'Ajudante_Encarregado'])): ?>
            <a href="/de/nova" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">➕ Lançar Nova DE</a>
        <?php endif; ?>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0; color: #002244; border-bottom: 2px solid #eee; padding-bottom: 10px;"><?= $is_search ? "🔍 Resultados Globais" : "📥 Sua Caixa de Entrada" ?></h3>
        
        <?php if (empty($lotes)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">Nenhum documento na sua jurisdição.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                        <th style="padding: 12px;">DE</th><th style="padding: 12px;">Origem</th><th style="padding: 12px;">Data</th><th style="padding: 12px; text-align: right;">Ações / Auditoria</th>
                    </tr>
                    <?php foreach ($lotes as $lote): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <code style="color: #d32f2f; font-weight: bold; font-size: 1.1em;"><?= htmlspecialchars($lote['numero_geral']) ?></code>
                            
                            <?php if (($lote['qtd_rejeitados'] ?? 0) > 0 && !$is_search): ?>
                                <span style="background: #dc3545; color: white; padding: 3px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold; margin-left: 8px;">⚠️ PENDÊNCIA</span>
                            <?php endif; ?>
                            
                            <?php if ($is_search): ?>
                                <br><small style="color:#004488; font-weight:bold;"><?= str_replace('_', ' ', htmlspecialchars($lote['status_inbox'])) ?></small>
                            <?php else: ?>
                                <?php 
                                    $status_representado = $lote['status_inbox'] ?? '';
                                    if ($role === 'Chefe_Departamento' && $status_representado === 'AGU_VRF_VICE_DIRETOR') {
                                        echo "<span style='background: #e83e8c; color: white; padding: 3px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold; margin-left: 8px;'>Assinando como Vice-Diretor</span>";
                                    } elseif ($role === 'Vice_Diretor' && $status_representado === 'AGU_ASS_DIRETOR') {
                                        echo "<span style='background: #e83e8c; color: white; padding: 3px 6px; border-radius: 10px; font-size: 0.75em; font-weight: bold; margin-left: 8px;'>Assinando como Diretor</span>";
                                    }
                                ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;"><b><?= htmlspecialchars($lote['origem_tipo']) ?></b> <small>(<?= htmlspecialchars($lote['criado_por']) ?>)</small></td>
                        <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></td>
                        <td style="padding: 12px; text-align: right;">
                            
                            <a href="/de/acompanhar?id=<?= $lote['id'] ?>" style="background: #17a2b8; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.9em; font-weight: bold;">🔍 Acompanhar Itens</a>
                            
                            <?php if (!$is_search): ?>
                                <?php if ($role === 'Protocolo'): ?>
                                    <a href="/protocolo/lote?id=<?= $lote['id'] ?>" style="background: #004488; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.9em; font-weight: bold; margin-left: 5px;">📂 Processar Protocolo</a>
                                <?php elseif (in_array($role, ['Enc_Financas', 'Ajudante_Encarregado', 'Chefe_Departamento', 'Vice_Diretor', 'Diretor'])): ?>
                                    <a href="/assinador/lote?id=<?= $lote['id'] ?>" style="background: #6f42c1; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 5px;">✍️ Analisar e Assinar</a>
                                <?php endif; ?>
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
        fetch('/api/check_inbox?t=' + new Date().getTime())
            .then(res => res.json())
            .then(data => {
                const alerta = document.getElementById('alerta-novo-doc');
                const badge = document.getElementById('radar-count');
                if (data.count > 0) {
                    badge.innerText = data.count;
                    alerta.style.display = 'block';
                } else {
                    alerta.style.display = 'none';
                }
            });
    }
    setInterval(checkRadar, 15000); 
    checkRadar(); 
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>