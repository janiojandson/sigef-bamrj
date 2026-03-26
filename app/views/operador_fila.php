<?php $page_title = 'Operador - SIGEF'; require __DIR__ . '/partials/header.php'; ?> 

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;"> 
    <h2 style="margin: 0; color: #002244;">⚙️ Fila de Execução Financeira</h2> 
    <a href="/" class="btn btn-secondary" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a> 
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
function renderTabela($itens, $acao_tipo, $placeholder_input = "", $nome_botao = "", $is_ob = false, $is_lote = false) { 
    if (empty($itens)) { echo "<p style='color: #28a745; font-weight: bold;'>✅ Fila limpa!</p>"; return; } 
     
    $map_tab = ['receber'=>'receber', 'inserir_np'=>'np', 'inserir_lf'=>'lf', 'atender_fin'=>'atendimento', 'inserir_op'=>'op', 'autorizar_cancelamento'=>'cancelar', 'inserir_ob'=>'ob'];
    $tab_atual = $map_tab[$acao_tipo] ?? 'receber';

    if ($is_lote) { 
        echo "<form action='/operador/acao' method='POST' id='form-{$acao_tipo}'>"; 
        echo "<input type='hidden' name='tipo_acao' value='{$acao_tipo}'>"; 
        echo "<input type='hidden' name='tab_origem' value='{$tab_atual}'>"; 
        echo "<div style='margin-bottom: 15px; padding: 15px; background: #e9ecef; border-radius: 6px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; border: 1px solid #ccc;'>"; 
        echo "<b style='color: #333;'>Ação em Lote (Selecione na tabela):</b>"; 
        if ($placeholder_input) echo "<input type='text' name='valor_input' placeholder='{$placeholder_input}' required style='padding: 10px; border: 1px solid #004488; border-radius: 4px; width: 250px;'>"; 
        echo "<button type='submit' class='btn btn-primary' style='padding: 10px 20px; font-weight:bold;'>{$nome_botao}</button></div>"; 
    } 

    echo '<div class="table-responsive"><table style="width: 100%; border-collapse: collapse; min-width: 900px;"> 
          <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">'; 
     
    if ($is_lote) echo '<th style="padding:12px; width: 40px; text-align: center;"><input type="checkbox" onclick="toggleCheckboxes(this, \'chk-'.$acao_tipo.'\')" style="transform: scale(1.3); cursor: pointer;"></th>'; 
     
    echo '<th style="padding:12px;">ID / DE / Origem</th><th style="padding:12px;">Doc / Fornecedor</th><th style="padding:12px;">Dados Sistêmicos</th><th style="padding:12px; text-align:right;">Ações Individuais</th></tr>'; 
     
    foreach($itens as $i) { 
        $is_rejeitado = str_contains($i['status_atual'] ?? '', 'REJEITADO') || str_contains($i['observacao_atual'] ?? '', 'DEVOLVIDO');
        $bg_color = $i['prioridade'] ? '#fff5f5' : ''; 
        if ($is_rejeitado) $bg_color = '#fff3cd; border-left: 5px solid #ffc107;'; 

        echo "<tr style='border-bottom: 1px solid #eee; background: {$bg_color}'>"; 
         
        if ($is_lote) echo "<td style='padding:12px; text-align: center;'><input type='checkbox' form='form-{$acao_tipo}' name='itens_selecionados[]' value='{$i['id']}' class='chk-{$acao_tipo}' style='transform: scale(1.4); cursor: pointer;'></td>"; 
         
        echo "<td style='padding:12px;'> 
                <span style='background:#333; color:white; padding:3px 6px; border-radius:3px; font-size:0.85em; font-weight:bold; font-family: monospace;'>#".str_pad($i['id'], 5, '0', STR_PAD_LEFT)."</span><br> 
                <b style='margin-top: 5px; display: inline-block;'>DE: {$i['numero_geral']}</b><br> 
                <small style='color: #666;'>{$i['origem_tipo']}</small> ";
        if ($is_rejeitado) echo "<br><span style='display:inline-block; margin-top:5px; background: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 0.75em; font-weight: bold;'>⚠️ DEVOLVIDO (VERIFIQUE)</span>";
        echo "</td>"; 

        echo "<td style='padding:12px;'> 
                NF: <b>{$i['num_documento_fiscal']}</b> " . ($i['prioridade'] ? '🚩' : '') . "<br> 
                <small>CNPJ: <b>{$i['cpf_cnpj']}</b></small>"; 
        if (!empty($i['ns_numero'])) echo "<br><span style='background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold; margin-top:4px; display:inline-block;'>NS: {$i['ns_numero']}</span>"; 
        echo "</td>"; 

        echo "<td style='padding:12px; font-size: 0.9em; line-height: 1.4;'>"; 
        if(!empty($i['np_numero'])) echo "NP: <b style='color:#004488'>{$i['np_numero']}</b><br>"; 
        if(!empty($i['lf_numero'])) echo "LF: <b style='color:#17a2b8'>{$i['lf_numero']}</b><br>"; 
        if(!empty($i['op_numero'])) echo "OP: <b style='color:#6f42c1'>{$i['op_numero']}</b>"; 
        echo "</td>"; 

        echo "<td style='padding:12px; text-align:right;'>"; 
         
        // 🛡️ AÇÃO DA OB (Formulário de Upload Individual)
        if ($is_ob) {
            echo "<form action='/operador/acao' method='POST' enctype='multipart/form-data' style='display:flex; flex-direction:column; gap:8px; align-items:flex-end; margin-bottom:5px; background: #f1f3f5; padding: 10px; border-radius: 6px; border: 1px solid #ccc;'>
                    <input type='hidden' name='item_id' value='{$i['id']}'>
                    <input type='hidden' name='tipo_acao' value='inserir_ob'>
                    <input type='hidden' name='tab_origem' value='ob'>
                    
                    <div style='display:flex; gap:5px; width:100%; justify-content:space-between; align-items: center;'>
                        <b style='color:#004488; font-size: 0.9em;'>Liquidar:</b>
                        <div style='display:flex; gap:5px;'>
                            <input type='text' name='valor_input' placeholder='Nº da OB...' required style='padding:6px; border:1px solid #004488; border-radius:4px; width: 140px;'>
                            <input type='date' name='data_pagamento' required style='padding:6px; border:1px solid #004488; border-radius:4px;'>
                        </div>
                    </div>
                    
                    <div style='display:flex; gap:5px; width:100%; justify-content:space-between; align-items:center;'>
                        <input type='file' id='file_{$i['id']}' name='ob_arquivo' accept='.pdf' required style='font-size:0.85em; max-width:200px;'>
                        <div style='display:flex; gap: 5px;'>
                            <button type='submit' class='btn btn-success' style='padding:6px 12px; font-weight:bold; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>🏦 Salvar e Arquivar</button>
                        </div>
                    </div>
                  </form>";
        } else {
            // Botões padrões (Reiniciar e Devolver) para outras abas
            echo "<div style='display: flex; gap: 5px; justify-content: flex-end;'>";
            if (in_array($acao_tipo, ['receber', 'inserir_np', 'inserir_lf', 'atender_fin', 'inserir_op'])) {
                echo "<button type='button' onclick=\"reiniciarItem({$i['id']})\" class='btn btn-info' style='padding: 6px 12px; font-weight:bold; font-size: 0.85em; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;'>🔄 Reiniciar (Zerar)</button>";
            }
            echo "<button type='button' onclick=\"rejeitarParaOmap({$i['id']}, '{$tab_atual}')\" class='btn btn-outline-danger' style='padding: 6px 12px; font-weight:bold; font-size: 0.85em; background: transparent; border: 1px solid #dc3545; color: #dc3545; border-radius: 4px; cursor: pointer;'>❌ Devolver OMAP</button>"; 
            echo "</div>";
        }
         
        echo "</td></tr>"; 
    } 
    echo "</table></div>"; 
     
    if ($is_lote) echo "</form>"; 
} 
?> 

<form id="master-rej-form" method="POST" action="/operador/acao" style="display:none;">
    <input type="hidden" name="tipo_acao" value="rejeitar">
    <input type="hidden" name="item_id" id="m_rej_id">
    <input type="hidden" name="observacao" id="m_rej_obs">
    <input type="hidden" name="tab_origem" id="m_rej_tab">
</form>

<form id="master-rei-form" method="POST" action="/operador/acao" style="display:none;">
    <input type="hidden" name="tipo_acao" value="reiniciar">
    <input type="hidden" name="item_id" id="m_rei_id">
    <input type="hidden" name="tab_origem" value="receber">
</form>

<div id="receber" class="tab-content" style="display:block; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0;">1. Fila de Entrada e Devoluções</h3> 
    <?php renderTabela($itens_receber, 'receber', '', '✅ Aceitar Carga Selecionada', false, true); ?> 
</div> 

<div id="np" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0;">2. Digitação da NP (Em Bloco)</h3> 
    <?php renderTabela($itens_np, 'inserir_np', 'Nº da NP...', 'Salvar NP nos Selecionados', false, true); ?> 
</div> 

<div id="lf" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0;">3. Digitação da LF (Em Bloco)</h3> 
    <?php renderTabela($itens_lf, 'inserir_lf', 'Nº da LF...', 'Salvar LF nos Selecionados', false, true); ?> 
</div> 

<div id="atendimento" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0;">4. Atendimento Financeiro (Em Bloco)</h3> 
    <?php renderTabela($itens_atendimento, 'atender_fin', '', '✔️ Marcar Atendidos', false, true); ?> 
</div> 

<div id="op" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0;">5. Digitação da OP (Em Bloco)</h3> 
    <?php renderTabela($itens_op, 'inserir_op', 'Nº da OP...', 'Salvar OP nos Selecionados', false, true); ?> 
</div> 

<div id="rap" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0;">6. Gerar RAP</h3> 
    <?php if (empty($itens_rap)): ?> 
        <p style='color: #28a745; font-weight: bold;'>✅ Nenhuma OP aguardando RAP!</p> 
    <?php else: ?> 
        <form action="/operador/gerar_rap" method="POST"> 
            <table style="width: 100%; border-collapse: collapse; min-width: 900px; margin-bottom: 15px;"> 
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;"> 
                    <th style="padding:10px; width: 40px; text-align: center;"><input type="checkbox" onclick="toggleCheckboxes(this, 'chk-rap')" style="transform: scale(1.3); cursor: pointer;" checked></th> 
                    <th style="padding:10px; width: 60px;">ID</th> 
                    <th style="padding:10px;">Doc (NF) / OP</th> 
                    <th style="padding:10px;">CNPJ / NS</th> 
                </tr> 
                <?php foreach($itens_rap as $i): ?> 
                <tr style="border-bottom: 1px solid #eee;"> 
                    <td style="padding:10px; text-align: center;"><input type="checkbox" name="itens_selecionados[]" value="<?= $i['id'] ?>" class="chk-rap" style="transform: scale(1.3); cursor: pointer;" checked></td> 
                    <td style="padding:10px;"><span style="background: #333; color: white; padding: 2px 5px; border-radius: 3px; font-family: monospace;">#<?= str_pad($i['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                    <td style="padding:10px;">NF: <b><?= htmlspecialchars($i['num_documento_fiscal']) ?></b><br>OP: <b style='color:#6f42c1'><?= htmlspecialchars($i['op_numero'] ?? '') ?></b></td> 
                    <td style="padding:10px;"> 
                        <b><?= htmlspecialchars($i['cpf_cnpj']) ?></b><br> 
                        <?php if (!empty($i['ns_numero'])): ?> 
                            <span style="background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold;">NS: <?= htmlspecialchars($i['ns_numero']) ?></span> 
                        <?php endif; ?> 
                    </td> 
                </tr> 
                <?php endforeach; ?> 
            </table> 
            <button type="submit" style="background: #004488; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 1.1em;">🚀 Gerar RAP e Enviar para Assinatura</button> 
        </form> 
    <?php endif; ?> 
</div> 

<div id="ob" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0; color:#28a745;">7. Digitação da OB Final</h3> 
    <?php renderTabela($itens_ob, 'inserir_ob', 'Nº da OB...', '🏦 Liquidar', true, false); ?> 
</div> 

<div id="cancelar" class="tab-content" style="display:none; background:white; padding:20px; border-radius:0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"> 
    <h3 style="margin-top:0; color:#dc3545;">8. Aval de Cancelamento</h3> 
    <?php renderTabela($itens_cancelar, 'autorizar_cancelamento', '', '✔️ Dar Baixa', false, true); ?> 
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

function toggleCheckboxes(source, className) { 
    var checkboxes = document.getElementsByClassName(className); 
    for(var i=0, n=checkboxes.length; i<n; i++) {  
        checkboxes[i].checked = source.checked;  
    } 
} 

function rejeitarParaOmap(id, abaOrigem) {
    let motivo = prompt("Motivo da devolução para a OMAP (Obrigatório):");
    if (motivo) {
        document.getElementById('m_rej_id').value = id;
        document.getElementById('m_rej_obs').value = motivo;
        document.getElementById('m_rej_tab').value = abaOrigem;
        document.getElementById('master-rej-form').submit();
    }
}

function reiniciarItem(id) {
    if (confirm("ATENÇÃO: Apagar NP, LF, OP e resetar a liquidação deste item?")) {
        document.getElementById('m_rei_id').value = id;
        document.getElementById('master-rei-form').submit();
    }
}

const urlParams = new URLSearchParams(window.location.search); 
const activeTab = urlParams.get('tab') || '<?= $aba_ativa ?? "receber" ?>'; 
openTab(activeTab); 
</script> 
<?php require __DIR__ . '/partials/footer.php'; ?>
