<?php $page_title = 'Operador - SIGEF'; require __DIR__ . '/partials/header.php'; ?> 

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;"> 
    <h2 style="margin: 0; color: #002244;">⚙️ Fila de Execução Financeira</h2> 
    <a href="/" class="btn btn-secondary">⬅️ Dashboard</a> 
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

    if ($is_lote) { 
        echo "<form action='/operador/acao' method='POST' id='form-{$acao_tipo}'>"; 
        echo "<input type='hidden' name='tipo_acao' value='{$acao_tipo}'>"; 
        echo "<input type='hidden' name='tab_origem' value='{$tab_atual}'>"; 
        echo "<div style='margin-bottom: 15px; padding: 15px; background: #e9ecef; border-radius: 6px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; border: 1px solid #ccc;'>"; 
        echo "<b style='color: #333;'>Ação em Lote (Selecione na tabela):</b>"; 
        if ($placeholder_input) echo "<input type='text' name='valor_input' placeholder='{$placeholder_input}' required style='padding: 10px; border: 1px solid #004488; border-radius: 4px; width: 250px;'>"; 
        echo "<button type='submit' class='btn btn-primary' style='padding: 10px 20px; font-weight:bold;'>{$nome_botao}</button></div>"; 
    } 

    echo '<div class="table-responsive">';
    echo "<table class='tabela-filtravel' style='width: 100%; border-collapse: collapse; min-width: 900px; font-size: 0.9em;'>";
    
    // Cabeçalho
    echo '<tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">';
    if ($is_lote) echo '<th style="padding: 10px; width: 40px; text-align: center;"><input type="checkbox" onclick="toggleCheckboxes(this)" style="transform: scale(1.2); cursor: pointer;"></th>';
    echo '<th style="padding: 10px;">ID / DE</th>';
    echo '<th style="padding: 10px;">Fornecedor / CNPJ</th>';
    echo '<th style="padding: 10px;">Nº Doc / NS</th>';
    if ($acao_tipo === 'inserir_np') echo '<th style="padding: 10px;">NP</th>';
    if ($acao_tipo === 'inserir_lf') echo '<th style="padding: 10px;">LF</th>';
    if ($acao_tipo === 'inserir_op') echo '<th style="padding: 10px;">OP</th>';
    if ($acao_tipo === 'inserir_ob') echo '<th style="padding: 10px;">OB / Data PGT</th>';
    if ($acao_tipo === 'atender_fin') echo '<th style="padding: 10px;">Atendimento</th>';
    echo '<th style="padding: 10px;">Status / Obs</th>';
    echo '</tr>';
    
    foreach ($itens as $item) {
        $texto_filtravel = strtolower(implode(' ', [
            $item['id'], $item['numero_geral'] ?? '', $item['cpf_cnpj'] ?? '', 
            $item['empresa_nome'] ?? '', $item['num_documento_fiscal'] ?? '',
            $item['ns_numero'] ?? '', $item['np_numero'] ?? '', $item['op_numero'] ?? '',
            $item['lf_numero'] ?? '', $item['ob_numero'] ?? '', $item['status_atual'] ?? ''
        ]));
        
        echo "<tr class='linha-dado' data-filtro='" . htmlspecialchars($texto_filtravel) . "' style='border-bottom: 1px solid #eee; " . ($item['prioridade'] ? 'background: #fff5f5;' : '') . "'>";
        
        if ($is_lote) {
            echo '<td style="padding: 10px; text-align: center;"><input type="checkbox" name="itens_selecionados[]" value="' . $item['id'] . '" class="item-checkbox" style="transform: scale(1.2); cursor: pointer;"></td>';
        }
        
        echo '<td style="padding: 10px;"><code style="color: #d32f2f; font-weight: bold;">#' . str_pad($item['id'], 5, '0', STR_PAD_LEFT) . '</code><br><small style="color: #666;">' . htmlspecialchars($item['numero_geral'] ?? '') . '</small></td>';
        echo '<td style="padding: 10px;"><b>' . htmlspecialchars($item['empresa_nome'] ?? 'N/I') . '</b><br><small style="color: #666;">CNPJ: ' . htmlspecialchars($item['cpf_cnpj'] ?? '') . '</small></td>';
        echo '<td style="padding: 10px;">NF: <b>' . htmlspecialchars($item['num_documento_fiscal'] ?? '') . '</b>';
        if (!empty($item['ns_numero'])) echo '<br><span style="background: #ffcc00; color: #002244; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; font-weight: bold;">📌 NS: ' . htmlspecialchars($item['ns_numero']) . '</span>';
        echo '</td>';
        
        if ($acao_tipo === 'inserir_np') echo '<td style="padding: 10px;">' . htmlspecialchars($item['np_numero'] ?? '-') . '</td>';
        if ($acao_tipo === 'inserir_lf') echo '<td style="padding: 10px;">' . htmlspecialchars($item['lf_numero'] ?? '-') . '</td>';
        if ($acao_tipo === 'inserir_op') echo '<td style="padding: 10px;">' . htmlspecialchars($item['op_numero'] ?? '-') . '</td>';
        if ($acao_tipo === 'inserir_ob') echo '<td style="padding: 10px;">OB: ' . htmlspecialchars($item['ob_numero'] ?? '-') . '<br>Data: ' . htmlspecialchars($item['data_pagamento'] ?? '-') . '</td>';
        if ($acao_tipo === 'atender_fin') echo '<td style="padding: 10px;">' . htmlspecialchars($item['lf_numero'] ?? '-') . '</td>';
        
        echo '<td style="padding: 10px; font-size: 0.85em; color: #555;">' . htmlspecialchars($item['status_atual'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table></div>';
    
    if ($is_lote) echo '</form>';
}

// Abas
$tabs = [
    'receber' => ['itens' => $itens_receber, 'titulo' => '📥 Receber'],
    'np' => ['itens' => $itens_np, 'titulo' => '📝 NP'],
    'lf' => ['itens' => $itens_lf, 'titulo' => '📑 LF'],
    'atendimento' => ['itens' => $itens_atendimento, 'titulo' => '💳 Atendimento'],
    'op' => ['itens' => $itens_op, 'titulo' => '📄 OP'],
    'rap' => ['itens' => $itens_rap, 'titulo' => '🚀 RAP'],
    'ob' => ['itens' => $itens_ob, 'titulo' => '🏦 OB'],
    'cancelar' => ['itens' => $itens_cancelar, 'titulo' => '🗑️ Aval Canc.'],
];
?>

<?php foreach ($tabs as $tab_id => $tab_info): ?>
<div id="tab-<?= $tab_id ?>" class="tab-content" style="display: none;">
    <h3 style="color: #002244; margin-bottom: 15px;"><?= $tab_info['titulo'] ?> (<?= count($tab_info['itens']) ?>)</h3>
    <?php 
    switch($tab_id) {
        case 'receber': renderTabela($tab_info['itens'], 'receber', '', '✅ Confirmar Recebimento', false, true); break;
        case 'np': renderTabela($tab_info['itens'], 'inserir_np', 'Número da NP', '📝 Inserir NP', false, true); break;
        case 'lf': renderTabela($tab_info['itens'], 'inserir_lf', 'Número da LF', '📑 Inserir LF', false, true); break;
        case 'atendimento': renderTabela($tab_info['itens'], 'atender_fin', '', '💳 Atender', false, true); break;
        case 'op': renderTabela($tab_info['itens'], 'inserir_op', 'Número da OP', '📄 Inserir OP', false, true); break;
        case 'rap': renderTabela($tab_info['itens'], 'gerar_rap', '', '🚀 Gerar RAP', false, true); break;
        case 'ob': renderTabela($tab_info['itens'], 'inserir_ob', 'Nº OB | Data PGT (dd/mm/aaaa)', '🏦 Inserir OB', true, true); break;
        case 'cancelar': renderTabela($tab_info['itens'], 'autorizar_cancelamento', '', '🗑️ Autorizar Cancelamento', false, true); break;
    }
    ?>
</div>
<?php endforeach; ?>

<script>
function openTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabName).style.display = 'block';
    document.getElementById('btn-' + tabName).classList.add('active');
}

function filtrarTodasTabelas() {
    const termo = document.getElementById('filtroGlobal').value.toLowerCase();
    document.querySelectorAll('.linha-dado').forEach(linha => {
        const dados = linha.getAttribute('data-filtro') || '';
        linha.style.display = dados.includes(termo) ? '' : 'none';
    });
}

function toggleCheckboxes(master) {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = master.checked);
}

// Abre a aba correta ao carregar
const tabParam = '<?= $_GET['tab'] ?? 'receber' ?>';
openTab(tabParam);
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>