<?php $page_title = 'Operador - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">⚙️ Fila de Execução Financeira</h2>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 3px solid #004488; padding-bottom: 5px; overflow-x: auto; white-space: nowrap;">
    <button id="btn-receber" class="tab-btn" onclick="openTab('receber')">📥 Receber (<?= count($itens_receber) ?>)</button>
    <button id="btn-np" class="tab-btn" onclick="openTab('np')">📝 NP (<?= count($itens_np) ?>)</button>
    <button id="btn-lf" class="tab-btn" onclick="openTab('lf')">📑 LF (<?= count($itens_lf) ?>)</button>
    <button id="btn-atendimento" class="tab-btn" onclick="openTab('atendimento')">💳 Atendimento (<?= count($itens_atendimento) ?>)</button>
    <button id="btn-op" class="tab-btn" onclick="openTab('op')">📄 OP (<?= count($itens_op) ?>)</button>
    <button id="btn-rap" class="tab-btn" onclick="openTab('rap')">🚀 Gerar RAP (<?= count($itens_rap) ?>)</button>
    <button id="btn-ob" class="tab-btn" onclick="openTab('ob')">🏦 OB Final (<?= count($itens_ob) ?>)</button>
</div>

<?php
function renderTabela($itens, $acao_tipo, $placeholder_input = "", $nome_botao = "", $mostrar_data = false) {
    if (empty($itens)) { echo "<p style='color: #28a745; font-weight: bold;'>✅ Fila limpa!</p>"; return; }
    echo '<table style="width: 100%; border-collapse: collapse; min-width: 900px;">
          <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
          <th style="padding:10px;">Lote/Doc</th><th style="padding:10px;">CNPJ</th><th style="padding:10px;">Valor</th><th style="padding:10px; width:380px;">Ações</th></tr>';
    
    foreach($itens as $i) {
        echo "<tr style='border-bottom: 1px solid #eee; " . ($i['prioridade'] ? 'background: #fff5f5;' : '') . "'>";
        echo "<td style='padding:10px;'><b>{$i['numero_geral']}</b><br>NF: {$i['num_documento_fiscal']} " . ($i['prioridade'] ? '🚩' : '') . "</td>";
        echo "<td style='padding:10px;'>{$i['cpf_cnpj']}</td>";
        echo "<td style='padding:10px; color:#28a745; font-weight:bold;'>R$ " . number_format($i['valor_total'], 2, ',', '.') . "</td>";
        
        echo "<td style='padding:10px; text-align:right;'>";
        // Formulário de Ação Principal
        echo "<form action='/operador/acao' method='POST' style='display:flex; gap:5px; justify-content:flex-end; margin-bottom:5px;'>
                <input type='hidden' name='item_id' value='{$i['id']}'><input type='hidden' name='tipo_acao' value='{$acao_tipo}'>";
        
        if ($placeholder_input) {
            echo "<input type='text' name='valor_input' placeholder='{$placeholder_input}' required style='padding:6px; border:1px solid #ccc; border-radius:4px; flex:1;'>";
        }
        if ($mostrar_data) {
            echo "<input type='date' name='data_pagamento' required style='padding:6px; border:1px solid #ccc; border-radius:4px;'>";
        }
        echo "<button type='submit' style='background:#004488; color:white; border:none; padding:6px 12px; border-radius:4px; font-weight:bold; cursor:pointer;'>{$nome_botao}</button>
              </form>";

        // Botão de Rejeitar (Universal)
        echo "<button onclick='mostrarRejeicao({$i['id']})' style='background:#dc3545; color:white; border:none; padding:4px 8px; border-radius:4px; font-size:0.85em; cursor:pointer;'>Rejeitar p/ Origem</button>
              <form action='/operador/acao' method='POST' id='form-rej-{$i['id']}' style='display:none; margin-top:5px; text-align:right;'>
                <input type='hidden' name='item_id' value='{$i['id']}'><input type='hidden' name='tipo_acao' value='rejeitar'>
                <input type='text' name='observacao' placeholder='Motivo...' required style='padding:4px; border:1px solid #dc3545; border-radius:4px; width:150px;'>
                <button type='submit' style='background:#dc3545; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;'>Confirmar</button>
              </form>";
        echo "</td></tr>";
    }
    echo "</table>";
}
?>

<div id="receber" class="tab-content" style="display:block; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">1. Entrada</h3>
    <?php renderTabela($itens_receber, 'receber', '', '✅ Receber'); ?>
</div>

<div id="np" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">2. Digitação da NP</h3>
    <?php renderTabela($itens_np, 'inserir_np', 'Nº da NP...', 'Salvar NP'); ?>
</div>

<div id="lf" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">3. Digitação da LF</h3>
    <?php renderTabela($itens_lf, 'inserir_lf', 'Nº da LF...', 'Salvar LF'); ?>
</div>

<div id="atendimento" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">4. Atendimento Financeiro</h3>
    <?php renderTabela($itens_atendimento, 'atender_fin', '', '✔️ Marcar Atendido'); ?>
</div>

<div id="op" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">5. Digitação da OP</h3>
    <?php renderTabela($itens_op, 'inserir_op', 'Nº da OP...', 'Salvar OP'); ?>
</div>

<div id="rap" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0;">6. Gerar RAP e Enviar Assinadores</h3>
    <p style="color:#666; font-size:0.9em;">Ao clicar abaixo, o item sai desta fila e vai para o 1º Assinador (Enc. Finanças).</p>
    <?php renderTabela($itens_rap, 'gerar_rap', '', '🚀 Enviar Assinaturas'); ?>
</div>

<div id="ob" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top:0; color:#28a745;">7. Digitação da OB Final (Itens assinados pelo Diretor)</h3>
    <?php renderTabela($itens_ob, 'inserir_ob', 'Nº da OB...', '🏦 Liquidar e Arquivar', true); ?>
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
    document.getElementById("btn-" + tabName).style.color = "white";
}
function mostrarRejeicao(id) {
    var form = document.getElementById('form-rej-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
openTab('receber'); // Aba inicial
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>