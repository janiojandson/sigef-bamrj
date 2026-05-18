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

<!-- 🔍 Filtro Global em Tempo Real -->
<div style="margin-bottom: 15px;">
    <input type="text" id="filtroGlobal" class="filtro-real" placeholder="🔍 Filtrar por ID, CNPJ, Fornecedor, NF, OP, RAP..." onkeyup="filtrarTodasTabelas()">
</div>

<?php 
function renderTabela($itens, $acao_tipo, $placeholder_input = "", $nome_botao = "", $is_ob = false, $is_lote = false) { 
    if (empty($itens)) { echo "<p style='color: #28a745; font-weight: bold;'>✅ Fila limpa!</p>"; return; } 
     
    $map_tab = ['receber'=>'receber', 'inserir_np'=>'np', 'inserir_lf'=>'lf', 'atender_fin'=>'atendimento', 'inserir_op'=>'op', 'autorizar_cancelamento'=>'cancelar', 'inserir_ob'=>'ob'];
    $tab_atual = $map_tab[$acao_tipo] ?? 'receber';

    $enctype = $is_ob ? 'enctype="multipart/form-data"' : '';

    if ($is_lote) { 
        $action_url = ($acao_tipo === 'gerar_rap') ? '/operador/gerar_rap' : '/operador/acao';
        echo "<form action='{$action_url}' method='POST' {$enctype} id='form-{$acao_tipo}'>"; 
        echo "<input type='hidden' name='tipo_acao' value='{$acao_tipo}'>"; 
        echo "<input type='hidden' name='tab_origem' value='{$tab_atual}'>"; 
        echo "<div style='margin-bottom: 15px; padding: 15px; background: #e9ecef; border-radius: 6px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; border: 1px solid #ccc; flex-wrap: wrap;'>"; 
        echo "<b style='color: #333;'>Ação em Lote (Selecione na tabela):</b>"; 
        if ($placeholder_input) echo "<input type='text' name='valor_input' placeholder='{$placeholder_input}' required style='padding: 10px; border: 1px solid #004488; border-radius: 4px; width: 250px;'>"; 
        
        // 🏦 FASE 1: Campos extras para OB — Data de Pagamento e Upload Comprovativo
        if ($is_ob) {
            echo "<div style='display: flex; flex-direction: column; gap: 5px; align-items: flex-end;'>";
            echo "<div style='display: flex; gap: 10px; align-items: center;'>";
            echo "<input type='date' name='data_pagamento' required style='padding: 10px; border: 1px solid #004488; border-radius: 4px; width: 180px;' title='Data de Pagamento'>";
            echo "<input type='file' id='ob_file_input' name='ob_comprovativo[]' multiple accept='.pdf,.jpg,.jpeg,.png' style='padding: 8px; border: 1px solid #004488; border-radius: 4px; font-size: 0.9em; width: 280px;' title='Comprovativos da OB (Múltiplos)' onchange='updateFileList(this)'>";
            echo "</div>";
            echo "<div id='ob_file_list' style='font-size: 0.85em; display: flex; flex-direction: column; gap: 3px; width: 100%; align-items: flex-end;'></div>";
            echo "</div>";
        }
        
        echo "<button type='submit' class='btn btn-primary' style='padding: 10px 20px; font-weight:bold; height: 44px;'>{$nome_botao}</button>";
        if ($acao_tipo === 'inserir_op') {
            echo "<button type='button' onclick='abrirModalAjusteLote()' class='btn btn-warning' style='padding: 10px 20px; font-weight:bold; height: 44px; background: #ffc107; color: #000; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;'>⚡ Ajuste em Lote</button>";
        }
        echo "</div>";
    } 

    echo '<div class="table-responsive"><table style="width:100%; border-collapse:collapse; font-size:0.9em; min-width:800px;">';
    echo '<tr style="background:#002244; color:white;">';
    if ($is_lote) echo '<th style="padding:10px; width:40px;"><input type="checkbox" onclick="toggleAll(this, \'' . $acao_tipo . '\')"></th>';
    echo '<th style="padding:10px;">ID / DE</th>';
    echo '<th style="padding:10px;">CNPJ / Fornecedor</th>';
    echo '<th style="padding:10px;">NF / Doc</th>';
    echo '<th style="padding:10px;">Vencimento</th>';
    echo '<th style="padding:10px;">NS</th>';
    if ($acao_tipo === 'inserir_np') echo '<th style="padding:10px;">NP</th>';
    if ($acao_tipo === 'inserir_lf') echo '<th style="padding:10px;">LF</th>';
    if ($acao_tipo === 'inserir_op') echo '<th style="padding:10px;">OP</th>';
    if ($acao_tipo === 'inserir_ob') echo '<th style="padding:10px;">OP / RAP</th>';
    echo '<th style="padding:10px;">Status</th>';
    echo '<th style="padding:10px;">Prioridade</th>';
    if ($acao_tipo !== 'autorizar_cancelamento') {
        echo '<th style="padding:10px; text-align:right;">Ações Individuais</th>';
    }
    echo '</tr>';

    foreach ($itens as $item) {
        $prioridade_badge = $item['prioridade'] ? '<span style="background:#dc3545; color:white; padding:2px 6px; border-radius:3px; font-size:0.8em;">🔴 URG</span>' : '<span style="color:#28a745;">Normal</span>';
        $status_color = '#004488';
        if (str_contains($item['status_atual'], 'CANCELAMENTO')) $status_color = '#dc3545';
        $is_rejeitado = str_contains($item['status_atual'], 'REJEITADO');
        if ($is_rejeitado) $status_color = '#dc3545';

        echo '<tr style="border-bottom:1px solid #ddd;" class="filtro-linha">';
        if ($is_lote) echo '<td style="padding:8px; text-align:center;"><input type="checkbox" name="itens_selecionados[]" value="' . $item['id'] . '" class="chk-' . $acao_tipo . '"></td>';
        echo '<td style="padding:8px;"><b>#' . str_pad($item['id'], 5, '0', STR_PAD_LEFT) . '</b><br><small style="color:#666;">' . htmlspecialchars($item['numero_geral']) . '</small><br><span onclick="toggleHistoricoRow(' . $item['id'] . ')" style="cursor:pointer; color: #004488; font-weight: bold; margin-top: 5px; display:inline-block; font-size:0.85em;">🔽 Ver Histórico</span></td>';
        echo '<td style="padding:8px;"><small>' . htmlspecialchars($item['cpf_cnpj'] ?? '') . '</small><br><b>' . htmlspecialchars($item['empresa_nome'] ?? 'Não Informado') . '</b></td>';
        echo '<td style="padding:8px;">' . htmlspecialchars($item['num_documento_fiscal'] ?? '') . '</td>';
        $venc_str = !empty($item['data_vencimento']) ? date('d/m/Y', strtotime($item['data_vencimento'])) : '-';
        if (!empty($item['data_vencimento']) && strtotime($item['data_vencimento']) <= strtotime('+15 days')) {
            $venc_str = '<span style="color:#dc3545;font-weight:bold;" title="Vencimento Próximo/Expirado">⚠️ ' . $venc_str . '</span>';
        }
        echo '<td style="padding:8px;">' . $venc_str . '</td>';
        echo '<td style="padding:8px;">' . htmlspecialchars($item['ns_numero'] ?? '-') . '</td>';
        if ($acao_tipo === 'inserir_np') echo '<td style="padding:8px;">' . htmlspecialchars($item['np_numero'] ?? '-') . '</td>';
        if ($acao_tipo === 'inserir_lf') echo '<td style="padding:8px;">' . htmlspecialchars($item['lf_numero'] ?? '-') . '</td>';
        if ($acao_tipo === 'inserir_op') echo '<td style="padding:8px;">' . htmlspecialchars($item['op_numero'] ?? '-') . '</td>';
        $status_text = str_replace('AGUARDANDO_', '', str_replace('AGU_', '', $item['status_atual']));
        if ($is_rejeitado) {
            echo '<td style="padding:8px; text-align:center;"><span style="background:#dc3545; color:white; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:0.85em; display:inline-block;">' . $status_text . '</span></td>';
        } else {
            echo '<td style="padding:8px; color:' . $status_color . '; font-weight:bold; font-size:0.85em;">' . $status_text . '</td>';
        }
        echo '<td style="padding:8px; text-align:center;">' . $prioridade_badge . '</td>';
        
        if ($acao_tipo !== 'autorizar_cancelamento') {
            echo "<td style='padding:8px; text-align:right; white-space:nowrap;'>"; 
            echo "<div style='display: flex; gap: 5px; justify-content: flex-end;'>";
            if (in_array($acao_tipo, ['receber', 'inserir_np', 'inserir_lf', 'atender_fin', 'inserir_op', 'inserir_ob'])) {
                echo "<button type='button' onclick=\"reiniciarItem({$item['id']})\" class='btn btn-info' style='padding: 6px 12px; font-weight:bold; font-size: 0.85em; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;'>🔄 Reiniciar</button>";
            }
            if ($acao_tipo === 'inserir_op') {
                echo "<button type='button' onclick=\"abrirModalAjuste([{$item['id']}])\" class='btn btn-warning' style='padding: 6px 12px; font-weight:bold; font-size: 0.85em; background: #ffc107; color: #000; border: none; border-radius: 4px; cursor: pointer;'>⚡ Ajuste</button>";
            }
            echo "<button type='button' onclick=\"rejeitarParaOmap({$item['id']}, '{$tab_atual}')\" class='btn btn-outline-danger' style='padding: 6px 12px; font-weight:bold; font-size: 0.85em; background: transparent; border: 1px solid #dc3545; color: #dc3545; border-radius: 4px; cursor: pointer;'>❌ Devolver OMAP</button>"; 
            echo "</div>";
            echo "</td>";
        }
        echo '</tr>';
        echo '<tr id="hist-row-' . $item['id'] . '" style="display:none; background:#f8f9fa; border-bottom: 2px solid #ccc;"><td colspan="10" id="hist-content-' . $item['id'] . '" style="padding: 0;"></td></tr>';
    }
    echo '</table></div>';
    if ($is_lote) echo '</form>';
}

function renderTabelaSimples($itens, $acao_tipo) {
    if (empty($itens)) { echo "<p style='color: #28a745; font-weight: bold;'>✅ Fila limpa!</p>"; return; }
    echo '<div class="table-responsive"><table style="width:100%; border-collapse:collapse; font-size:0.9em; min-width:800px;">';
    echo '<tr style="background:#002244; color:white;">';
    echo '<th style="padding:10px;">ID / DE</th>';
    echo '<th style="padding:10px;">CNPJ / Fornecedor</th>';
    echo '<th style="padding:10px;">NF / Doc</th>';
    echo '<th style="padding:10px;">Vencimento</th>';
    echo '<th style="padding:10px;">NS</th>';
    echo '<th style="padding:10px;">Status</th>';
    echo '<th style="padding:10px;">Prioridade</th>';
    echo '</tr>';
    foreach ($itens as $item) {
        $prioridade_badge = $item['prioridade'] ? '<span style="background:#dc3545; color:white; padding:2px 6px; border-radius:3px; font-size:0.8em;">🔴 URG</span>' : '<span style="color:#28a745;">Normal</span>';
        echo '<tr style="border-bottom:1px solid #ddd;" class="filtro-linha">';
        echo '<td style="padding:8px;"><b>#' . str_pad($item['id'], 5, '0', STR_PAD_LEFT) . '</b><br><small style="color:#666;">' . htmlspecialchars($item['numero_geral']) . '</small><br><span onclick="toggleHistoricoRow(' . $item['id'] . ')" style="cursor:pointer; color: #004488; font-weight: bold; margin-top: 5px; display:inline-block; font-size:0.85em;">🔽 Ver Histórico</span></td>';
        echo '<td style="padding:8px;"><small>' . htmlspecialchars($item['cpf_cnpj'] ?? '') . '</small><br><b>' . htmlspecialchars($item['empresa_nome'] ?? 'Não Informado') . '</b></td>';
        echo '<td style="padding:8px;">' . htmlspecialchars($item['num_documento_fiscal'] ?? '') . '</td>';
        $venc_str = !empty($item['data_vencimento']) ? date('d/m/Y', strtotime($item['data_vencimento'])) : '-';
        if (!empty($item['data_vencimento']) && strtotime($item['data_vencimento']) <= strtotime('+15 days')) {
            $venc_str = '<span style="color:#dc3545;font-weight:bold;" title="Vencimento Próximo/Expirado">⚠️ ' . $venc_str . '</span>';
        }
        echo '<td style="padding:8px;">' . $venc_str . '</td>';
        echo '<td style="padding:8px;">' . htmlspecialchars($item['ns_numero'] ?? '-') . '</td>';
        echo '<td style="padding:8px; color:#004488; font-weight:bold; font-size:0.85em;">' . str_replace('AGUARDANDO_', '', str_replace('AGU_', '', $item['status_atual'])) . '</td>';
        echo '<td style="padding:8px; text-align:center;">' . $prioridade_badge . '</td>';
        echo '</tr>';
        echo '<tr id="hist-row-' . $item['id'] . '" style="display:none; background:#f8f9fa; border-bottom: 2px solid #ccc;"><td colspan="10" id="hist-content-' . $item['id'] . '" style="padding: 0;"></td></tr>';
    }
    echo '</table></div>';
}
?> 

<!-- Forms ocultos para ações individuais -->
<form id="master-rej-form" method="POST" action="/operador/acao" style="display:none;">
    <input type="hidden" name="tipo_acao" value="rejeitar">
    <input type="hidden" name="itens_selecionados[]" id="m_rej_id">
    <input type="hidden" name="valor_input" id="m_rej_obs">
    <input type="hidden" name="tab_origem" id="m_rej_tab">
</form>

<form id="master-rei-form" method="POST" action="/operador/acao" style="display:none;">
    <input type="hidden" name="tipo_acao" value="reiniciar">
    <input type="hidden" name="itens_selecionados[]" id="m_rei_id">
    <input type="hidden" name="tab_origem" value="receber">
</form>

<!-- Modal Reiniciar Processo -->
<div id="modalReiniciar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:25px; border-radius:8px; width:400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; color:#002244;">🔄 Reiniciar Processo</h3>
        <p style="color:#555;">Marque os campos que deseja <b>MANTER</b>. O processo retornará para a fase do último campo não marcado.</p>
        <form id="form-reiniciar-modal" method="POST" action="/operador/acao">
            <input type="hidden" name="tipo_acao" value="reiniciar_custom">
            <input type="hidden" name="itens_selecionados[]" id="rei_item_id">
            <input type="hidden" name="tab_origem" value="receber">
            
            <label style="display:block; margin-bottom:10px; cursor:pointer;"><input type="checkbox" name="keep_np" value="1" style="transform:scale(1.2);"> Manter NP</label>
            <label style="display:block; margin-bottom:10px; cursor:pointer;"><input type="checkbox" name="keep_lf" value="1" style="transform:scale(1.2);"> Manter LF</label>
            <label style="display:block; margin-bottom:20px; cursor:pointer;"><input type="checkbox" name="keep_op" value="1" style="transform:scale(1.2);"> Manter OP</label>
            
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('modalReiniciar').style.display='none'" style="padding:8px 15px; border-radius:4px; border:none; background:#ccc; cursor:pointer; font-weight:bold;">Cancelar</button>
                <button type="submit" style="padding:8px 15px; border-radius:4px; border:none; background:#004488; color:white; cursor:pointer; font-weight:bold;">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajuste (Pular para OB com Registro de OP) -->
<div id="modalAjuste" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:25px; border-radius:8px; width:450px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; color:#002244;">⚡ Ajuste - Pular para OB</h3>
        <p style="color:#555;">Este ajuste registrará a <b>OP (Ordem de Pagamento)</b> nos itens selecionados e os moverá direto para a fase da <b>OB (Ordem Bancária)</b>.</p>
        <form id="form-ajuste-modal" method="POST" action="/operador/acao">
            <input type="hidden" name="tipo_acao" value="pular_para_ob">
            <input type="hidden" name="tab_origem" value="op">
            <div id="ajuste_ids_container"></div>
            
            <div style="margin-bottom: 20px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#333;">Número da OP:</label>
                <input type="text" name="valor_input" id="ajuste_op_numero" placeholder="Digite o número da OP" required style="padding: 10px; border: 1px solid #004488; border-radius: 4px; width: 100%; box-sizing: border-box;">
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('modalAjuste').style.display='none'" style="padding:8px 15px; border-radius:4px; border:none; background:#ccc; cursor:pointer; font-weight:bold;">Cancelar</button>
                <button type="submit" style="padding:8px 15px; border-radius:4px; border:none; background:#ffc107; color:#000; cursor:pointer; font-weight:bold;">⚡ Confirmar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<!-- TAB: Receber -->
<div id="tab-receber" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">📥 Receber da Execução Financeira</h3>
    <?php renderTabela($itens_receber, 'receber', '', '✅ Confirmar Recebimento', false, true); ?>
</div>

<!-- TAB: NP -->
<div id="tab-np" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">📝 Inserir NP (Nota de Pagamento)</h3>
    <?php renderTabela($itens_np, 'inserir_np', 'Número da NP', '📝 Inserir NP', false, true); ?>
</div>

<!-- TAB: LF -->
<div id="tab-lf" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">📑 Inserir LF (Liquidação Financeira)</h3>
    <?php renderTabela($itens_lf, 'inserir_lf', 'Número da LF', '📑 Inserir LF', false, true); ?>
</div>

<!-- TAB: Atendimento -->
<div id="tab-atendimento" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">💳 Atendimento Financeiro</h3>
    <?php renderTabela($itens_atendimento, 'atender_fin', '', '💳 Concluir Atendimento', false, true); ?>
</div>

<!-- TAB: OP -->
<div id="tab-op" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">📄 Inserir OP (Ordem de Pagamento)</h3>
    <?php renderTabela($itens_op, 'inserir_op', 'Número da OP', '📄 Inserir OP', false, true); ?>
</div>

<!-- TAB: RAP -->
<div id="tab-rap" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">🚀 Gerar RAP (Relatório de Autorização de Pagamento)</h3>
    <?php renderTabela($itens_rap, 'gerar_rap', '', '🚀 Gerar RAP', false, true); ?>
</div>

<!-- 🏦 TAB: OB — FASE 1: Formulário completo com Número, Data e Upload -->
<div id="tab-ob" class="tab-content" style="display:none;">
    <h3 style="color:#002244;">🏦 Inserir OB (Ordem Bancária)</h3>
    <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
        <strong>⚠️ Atenção:</strong> Ao inserir a OB, o processo será <b>ARQUIVADO</b> automaticamente. Preencha o número da OB, a data de pagamento e anexe o comprovativo.
    </div>
    <?php renderTabela($itens_ob, 'inserir_ob', 'Número da OB', '🏦 Inserir OB e Arquivar', true, true); ?>
</div>

<!-- TAB: Cancelar -->
<div id="tab-cancelar" class="tab-content" style="display:none;">
    <h3 style="color:#dc3545;">🗑️ Avaliação de Cancelamento</h3>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #dc3545;">
        <strong>⚠️ Ação Irreversível:</strong> Ao autorizar o cancelamento, o processo será encerrado definitivamente.
    </div>
    <?php renderTabela($itens_cancelar, 'autorizar_cancelamento', '', '🗑️ Autorizar Cancelamento', false, true); ?>
</div>

<script>
function openTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabName).style.display = 'block';
    document.getElementById('btn-' + tabName).classList.add('active');
    localStorage.setItem('sigef_operador_tab', tabName);
}

// Restaura a última aba selecionada
const savedTab = localStorage.getItem('sigef_operador_tab') || '<?= $aba_ativa ?>';
if (document.getElementById('tab-' + savedTab)) {
    openTab(savedTab);
} else {
    openTab('receber');
}

function toggleAll(master, className) {
    document.querySelectorAll('.chk-' + className).forEach(cb => cb.checked = master.checked);
}

// 🔍 Filtro Global em Tempo Real
function filtrarTodasTabelas() {
    const termo = document.getElementById('filtroGlobal').value.toLowerCase();
    document.querySelectorAll('.filtro-linha').forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(termo) ? '' : 'none';
    });
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
    document.getElementById('rei_item_id').value = id;
    document.getElementById('modalReiniciar').style.display = 'flex';
}

function abrirModalAjuste(ids) {
    const container = document.getElementById('ajuste_ids_container');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'itens_selecionados[]';
        input.value = id;
        container.appendChild(input);
    });
    document.getElementById('ajuste_op_numero').value = '';
    document.getElementById('modalAjuste').style.display = 'flex';
}

function abrirModalAjusteLote() {
    const checkboxes = document.querySelectorAll('.chk-inserir_op:checked');
    if (checkboxes.length === 0) {
        alert("Selecione pelo menos um item!");
        return;
    }
    const ids = Array.from(checkboxes).map(cb => cb.value);
    abrirModalAjuste(ids);
}

async function toggleHistoricoRow(id) {
    const row = document.getElementById('hist-row-' + id);
    const content = document.getElementById('hist-content-' + id);
    
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
        if (content.innerHTML === '') {
            content.innerHTML = '<div style="padding:15px; text-align:center;">⏳ Carregando histórico...</div>';
            try {
                const response = await fetch('/historico/api?id=' + id);
                content.innerHTML = await response.text();
            } catch (err) {
                content.innerHTML = '<div style="padding:15px; color:red;">Erro ao carregar histórico.</div>';
            }
        }
    } else {
        row.style.display = 'none';
    }
}

// ==========================================
// Múltiplos Uploads de OB - Gerenciamento
// ==========================================
let selectedFiles = [];

function updateFileList(input) {
    for (let i = 0; i < input.files.length; i++) {
        selectedFiles.push(input.files[i]);
    }
    renderFileList();
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    renderFileList();
}

function renderFileList() {
    const listDiv = document.getElementById('ob_file_list');
    const input = document.getElementById('ob_file_input');
    
    if (!listDiv || !input) return;

    listDiv.innerHTML = '';
    
    const dt = new DataTransfer();
    
    selectedFiles.forEach((file, index) => {
        dt.items.add(file);
        
        const fileDiv = document.createElement('div');
        fileDiv.style.background = '#fff';
        fileDiv.style.border = '1px solid #ccc';
        fileDiv.style.padding = '3px 8px';
        fileDiv.style.borderRadius = '4px';
        fileDiv.style.display = 'inline-flex';
        fileDiv.style.alignItems = 'center';
        fileDiv.style.justifyContent = 'space-between';
        
        fileDiv.innerHTML = `
            <span>📄 ${file.name}</span>
            <button type="button" onclick="removeFile(${index})" style="background: none; border: none; color: #dc3545; font-weight: bold; cursor: pointer; margin-left: 10px;" title="Remover este arquivo">❌</button>
        `;
        listDiv.appendChild(fileDiv);
    });
    
    input.files = dt.files;
}
</script>

<style>
.tab-btn { padding: 8px 15px; border: none; background: #e9ecef; cursor: pointer; border-radius: 4px 4px 0 0; font-weight: bold; transition: 0.2s; }
.tab-btn:hover { background: #dee2e6; }
.tab-btn.active { background: #004488; color: white; }
.filtro-real { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
</style>

<?php require __DIR__ . '/partials/footer.php'; ?>