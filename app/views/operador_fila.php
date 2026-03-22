<?php $page_title = 'Operador - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">⚙️ Fila de Execução Financeira</h2>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 3px solid #004488; padding-bottom: 5px; overflow-x: auto; white-space: nowrap;">
    <button id="btn-receber" class="tab-btn" onclick="openTab('receber')">📥 Receber (<?= count($itens_receber) ?>)</button>
    <button id="btn-np" class="tab-btn" onclick="openTab('np')">📝 NP (<?= count($itens_np) ?>)</button>
    <button id="btn-lf" class="tab-btn" onclick="openTab('lf')">📑 LF (<?= count($itens_lf) ?>)</button>
    <button id="btn-atendimento" class="tab-btn" onclick="openTab('atendimento')">💳 Atend. (<?= count($itens_atendimento) ?>)</button>
    <button id="btn-op" class="tab-btn" onclick="openTab('op')">📄 OP (<?= count($itens_op) ?>)</button>
    <button id="btn-rap" class="tab-btn" onclick="openTab('rap')">🚀 RAP (<?= count($itens_rap) ?>)</button>
    <button id="btn-ob" class="tab-btn" onclick="openTab('ob')">🏦 OB (<?= count($itens_ob) ?>)</button>
    <button id="btn-cancelar" class="tab-btn" onclick="openTab('cancelar')" style="color: #dc3545;">🗑️ Aval Canc. (<?= count($itens_cancelar) ?>)</button>
</div>

<?php
function renderTabela($itens, $acao_tipo, $placeholder_input = "", $nome_botao = "", $is_ob = false) {
    if (empty($itens)) { echo "<p style='color: #28a745; font-weight: bold;'>✅ Fila limpa!</p>"; return; }
    echo '<table style="width: 100%; border-collapse: collapse; min-width: 900px;">
          <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
          <th style="padding:10px;">DE/Doc</th><th style="padding:10px;">Dados Inseridos</th><th style="padding:10px;">Valor</th><th style="padding:10px; width:380px; text-align:right;">Ações</th></tr>';
    
    foreach($itens as $i) {
        $dados_inseridos = "";
        if (!empty($i['pa_numero'])) $dados_inseridos .= "PA: <b style='color:#d32f2f'>{$i['pa_numero']}</b><br>";
        if (!empty($i['np_numero'])) $dados_inseridos .= "NP: <b style='color:#004488'>{$i['np_numero']}</b><br>";
        if (!empty($i['lf_numero'])) $dados_inseridos .= "LF: <b style='color:#17a2b8'>{$i['lf_numero']}</b><br>";
        if ($i['status_atual'] === 'AGUARDANDO_INSERCAO_OP' || !empty($i['op_numero']) || str_contains($i['status_atual'], 'RAP') || str_contains($i['status_atual'], 'OB')) {
            $dados_inseridos .= "Atend. Fin.: <b style='color:#28a745'>✔️ OK</b><br>";
        }
        if (!empty($i['op_numero'])) $dados_inseridos .= "OP: <b style='color:#6f42c1'>{$i['op_numero']}</b><br>";
        if (empty($dados_inseridos)) $dados_inseridos = "<span style='color:#999'>Aguardando...</span>";

        // 🛡️ DESTAQUE VERMELHO SE REJEITADO
        $bg_color = $i['prioridade'] ? '#fff5f5' : '';
        if (str_contains($i['status_atual'], 'REJEITADO')) $bg_color = '#ffeeba; border-left: 5px solid #dc3545;';

        echo "<tr style='border-bottom: 1px solid #eee; background: {$bg_color}'>";
        echo "<td style='padding:10px;'><b>DE: {$i['numero_geral']}</b><br>NF: {$i['num_documento_fiscal']} " . ($i['prioridade'] ? '🚩' : '') . "</td>";
        echo "<td style='padding:10px; font-size: 0.9em; line-height: 1.4;'>{$dados_inseridos}</td>";
        echo "<td style='padding:10px; color:#28a745; font-weight:bold;'>R$ " . number_format($i['valor_total'], 2, ',', '.') . "</td>";
        echo "<td style='padding:10px; text-align:right;'>";
        
        if ($is_ob) {
            // 🛡️ INPUT DE ARQUIVO COM BOTÃO DE LIMPAR
            echo "<form action='/operador/acao' method='POST' enctype='multipart/form-data' style='display:flex; flex-direction:column; gap:5px; align-items:flex-end; margin-bottom:5px;'>
                    <input type='hidden' name='item_id' value='{$i['id']}'><input type='hidden' name='tipo_acao' value='inserir_ob'>
                    <div style='display:flex; gap:5px; width:100%; justify-content:flex-end;'>
                        <input type='text' name='valor_input' placeholder='Nº da OB...' required style='padding:6px; border:1px solid #ccc; border-radius:4px; flex:1;'>
                        <input type='date' name='data_pagamento' required style='padding:6px; border:1px solid #ccc; border-radius:4px;'>
                    </div>
                    <div style='display:flex; gap:5px; width:100%; justify-content:flex-end; align-items:center; background:#f8f9fa; padding:5px; border-radius:4px;'>
                        <input type='file' id='file_{$i['id']}' name='ob_arquivo' accept='.pdf' required style='font-size:0.85em; max-width:200px;'>
                        <button type='button' onclick=\"document.getElementById('file_{$i['id']}').value=''\" style='background:#dc3545; color:white; border:none; padding:2px 6px; border-radius:4px; cursor:pointer;' title='Remover arquivo'>❌</button>
                        <button type='submit' style='background:#28a745; color:white; border:none; padding:6px 12px; border-radius:4px; font-weight:bold; cursor:pointer;'>🏦 Arquivar</button>
                    </div>
                  </form>";
        } else {
            echo "<form action='/operador/acao' method='POST' style='display:flex; gap:5px; justify-content:flex-end; margin-bottom:5px;'>
                    <input type='hidden' name='item_id' value='{$i['id']}'><input type='hidden' name='tipo_acao' value='{$acao_tipo}'>";
            if ($placeholder_input) echo "<input type='text' name='valor_input' placeholder='{$placeholder_input}' required style='padding:6px; border:1px solid #ccc; border-radius:4px; flex:1;'>";
            echo "<button type='submit' style='background:#28a745; color:white; border:none; padding:6px 12px; border-radius:4px; font-weight:bold; cursor:pointer;'>{$nome_botao}</button>
                  </form>";
        }

        echo "<div style='display:flex; justify-content:flex-end; gap:5px;'>";
        echo "<button onclick='mostrarRejeicao({$i['id']})' style='background:#dc3545; color:white; border:none; padding:4px 8px; border-radius:4px; font-size:0.85em; cursor:pointer;'>❌ Rejeitar</button>";
        if ($acao_tipo !== 'receber') {
            echo "<form action='/operador/acao' method='POST' onsubmit=\"return confirm('Reiniciar liquidação?')\" style='margin:0;'>
                    <input type='hidden' name='item_id' value='{$i['id']}'><input type='hidden' name='tipo_acao' value='reiniciar'>
                    <button type='submit' style='background:#ffcc00; color:#002244; border:none; padding:4px 8px; border-radius:4px; font-size:0.85em; cursor:pointer; font-weight:bold;'>🔄 Reiniciar</button>
                  </form>";
        }
        echo "</div>";

        echo "<form action='/operador/acao' method='POST' id='form-rej-{$i['id']}' style='display:none; margin-top:5px; text-align:right;'>
                <input type='hidden' name='item_id' value='{$i['id']}'><input type='hidden' name='tipo_acao' value='rejeitar'>
                <input type='text' name='observacao' placeholder='Motivo...' required style='padding:4px; border:1px solid #dc3545; border-radius:4px; width:180px;'>
                <button type='submit' style='background:#dc3545; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;'>Confirmar</button>
              </form>";
        
        echo "</td></tr>";
    }
    echo "</table>";
}
?>

<div id="receber" class="tab-content" style="display:block; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">1. Fila de Entrada</h3><?php renderTabela($itens_receber, 'receber', '', '✅ Receber Item'); ?>
</div>
<div id="np" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">2. Digitação da NP</h3><?php renderTabela($itens_np, 'inserir_np', 'Nº da NP...', 'Salvar NP'); ?>
</div>
<div id="lf" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">3. Digitação da LF</h3><?php renderTabela($itens_lf, 'inserir_lf', 'Nº da LF...', 'Salvar LF'); ?>
</div>
<div id="atendimento" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">4. Atendimento Financeiro</h3><?php renderTabela($itens_atendimento, 'atender_fin', '', '✔️ Marcar Atendido'); ?>
</div>
<div id="op" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">5. Digitação da OP</h3><?php renderTabela($itens_op, 'inserir_op', 'Nº da OP...', 'Salvar OP'); ?>
</div>

<div id="rap" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">6. Gerar RAP</h3>
    <?php if (empty($itens_rap)): ?>
        <p style='color: #28a745; font-weight: bold;'>✅ Nenhuma OP aguardando RAP!</p>
    <?php else: ?>
        <form action="/operador/gerar_rap" method="POST">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px; margin-bottom: 15px;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding:10px; width: 40px; text-align: center;">✅</th>
                    <th style="padding:10px;">Doc (NF) / OP</th>
                    <th style="padding:10px;">CNPJ</th>
                    <th style="padding:10px;">Valor</th>
                </tr>
                <?php foreach($itens_rap as $i): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding:10px; text-align: center;"><input type="checkbox" name="itens_selecionados[]" value="<?= $i['id'] ?>" style="transform: scale(1.3); cursor: pointer;" checked></td>
                    <td style="padding:10px;">NF: <b><?= htmlspecialchars($i['num_documento_fiscal']) ?></b><br>OP: <b style='color:#6f42c1'><?= htmlspecialchars($i['op_numero']) ?></b></td>
                    <td style="padding:10px;"><?= htmlspecialchars($i['cpf_cnpj']) ?></td>
                    <td style="padding:10px; color:#28a745; font-weight:bold;">R$ <?= number_format($i['valor_total'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <button type="submit" style="background: #004488; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 1.1em;">🚀 Gerar RAP e Imprimir</button>
        </form>
    <?php endif; ?>
</div>

<div id="ob" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0; color:#28a745;">7. Digitação da OB Final</h3>
    <?php renderTabela($itens_ob, 'inserir_ob', 'Nº da OB...', '🏦 Liquidar', true); ?>
</div>

<div id="cancelar" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0; color:#dc3545;">8. Aval de Cancelamento</h3>
    <?php renderTabela($itens_cancelar, 'autorizar_cancelamento', '', '✔️ Dar Baixa'); ?>
</div>

<style>
.tab-btn { background: #e9ecef; color: #333; border: none; padding: 10px 15px; font-weight: bold; cursor: pointer; border-radius: 4px 4px 0 0; }
</style>

<script>
function openTab(tabName) {
    var x = document.getElementsByClassName("tab-content");
    for (var i = 0; i < x.length; i++) { x[i].style.display = "none"; }
    var btns = document.getElementsByClassName("tab-btn");
    for (var i = 0; i < btns.length; i++) { btns[i].style.background = "#e9ecef"; btns[i].style.color = "#333"; }
    document.getElementById(tabName).style.display = "block";
    document.getElementById("btn-" + tabName).style.background = "#004488";
    if(tabName === 'cancelar') document.getElementById("btn-" + tabName).style.background = "#dc3545";
    document.getElementById("btn-" + tabName).style.color = "white";
}
function mostrarRejeicao(id) {
    var form = document.getElementById('form-rej-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
// 🛡️ LÊ A ABA ATIVA PELA URL
const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('tab') || '<?= $aba_ativa ?>';
openTab(activeTab);
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>