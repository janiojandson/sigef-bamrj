<?php
$page_title = 'Painel do Operador - SIGEF';
require __DIR__ . '/partials/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">⚙️ Central de Execução Financeira (Operador)</h2>
        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Atuação granular por Item/Nota Fiscal.</p>
    </div>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 3px solid #004488; padding-bottom: 5px; overflow-x: auto;">
    <button id="btn-receber" class="tab-btn" onclick="openTab('receber')" style="background: #004488; color: white; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 4px 4px 0 0;">📥 Receber (<?= count($itens_receber) ?>)</button>
    <button id="btn-np" class="tab-btn" onclick="openTab('np')" style="background: #e9ecef; color: #333; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 4px 4px 0 0;">📝 Inserir NP (<?= count($itens_np) ?>)</button>
    <button id="btn-lf" class="tab-btn" onclick="openTab('lf')" style="background: #e9ecef; color: #333; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 4px 4px 0 0;">📑 Inserir LF (<?= count($itens_lf) ?>)</button>
</div>

<div id="receber" class="tab-content" style="display: block; background: white; padding: 20px; border-radius: 0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">Fila de Entrada (Aguardando Aceite)</h3>
    <?php if (empty($itens_receber)): ?>
        <p style='color: #28a745; font-weight: bold;'>✅ Fila de recebimento limpa!</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                <th style="padding: 10px;">Prior.</th><th style="padding: 10px;">Lote/Origem</th><th style="padding: 10px;">CNPJ</th><th style="padding: 10px;">Doc (NF)</th><th style="padding: 10px;">Valor</th><th style="padding: 10px; text-align: right;">Ações</th>
            </tr>
            <?php foreach($itens_receber as $i): ?>
            <tr style="border-bottom: 1px solid #eee; <?= $i['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                <td style="padding: 10px; text-align: center;"><?= $i['prioridade'] ? '🚩' : '🏳️' ?></td>
                <td style="padding: 10px;"><b style="color: #d32f2f;"><?= htmlspecialchars($i['numero_geral']) ?></b><br><small><?= htmlspecialchars($i['origem_tipo']) ?></small></td>
                <td style="padding: 10px;"><?= htmlspecialchars($i['cpf_cnpj']) ?></td>
                <td style="padding: 10px;"><b><?= htmlspecialchars($i['num_documento_fiscal']) ?></b></td>
                <td style="padding: 10px; color: #28a745; font-weight: bold;">R$ <?= number_format($i['valor_total'], 2, ',', '.') ?></td>
                <td style="padding: 10px; text-align: right;">
                    <form action="/operador/acao" method="POST" style="display: inline;">
                        <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                        <input type="hidden" name="tipo_acao" value="receber">
                        <button type="submit" style="background: #004488; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;">✅ Receber</button>
                    </form>
                    <button onclick="mostrarRejeicao(<?= $i['id'] ?>)" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;">❌ Rejeitar</button>
                    
                    <form action="/operador/acao" method="POST" id="form-rej-<?= $i['id'] ?>" style="display: none; margin-top: 5px;">
                        <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                        <input type="hidden" name="tipo_acao" value="rejeitar">
                        <input type="text" name="observacao" placeholder="Motivo obrigatório" required style="padding: 5px; border: 1px solid #dc3545; border-radius: 4px; width: 140px;">
                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Confirmar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div id="np" class="tab-content" style="display: none; background: white; padding: 20px; border-radius: 0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">Aguardando Inserção de NP</h3>
    <?php if (empty($itens_np)): ?>
        <p style='color: #28a745; font-weight: bold;'>✅ Nenhuma NP pendente!</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                <th style="padding: 10px;">Lote/Doc</th><th style="padding: 10px;">CNPJ</th><th style="padding: 10px;">Valor</th><th style="padding: 10px; width: 350px;">Ação: Inserir NP</th>
            </tr>
            <?php foreach($itens_np as $i): ?>
            <tr style="border-bottom: 1px solid #eee; <?= $i['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                <td style="padding: 10px;"><b><?= htmlspecialchars($i['numero_geral']) ?></b><br>NF: <?= htmlspecialchars($i['num_documento_fiscal']) ?></td>
                <td style="padding: 10px;"><?= htmlspecialchars($i['cpf_cnpj']) ?></td>
                <td style="padding: 10px; color: #28a745; font-weight: bold;">R$ <?= number_format($i['valor_total'], 2, ',', '.') ?></td>
                <td style="padding: 10px;">
                    <form action="/operador/acao" method="POST" style="display: flex; gap: 5px;">
                        <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                        <input type="hidden" name="tipo_acao" value="inserir_np">
                        <input type="text" name="np_numero" placeholder="Ex: 2026NP000123" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex: 1;">
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; cursor: pointer;">Salvar NP</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div id="lf" class="tab-content" style="display: none; background: white; padding: 20px; border-radius: 0 8px 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">Aguardando Inserção de LF</h3>
    <?php if (empty($itens_lf)): ?>
        <p style='color: #28a745; font-weight: bold;'>✅ Nenhuma LF pendente!</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                <th style="padding: 10px;">Lote/Doc</th><th style="padding: 10px;">NP Registrada</th><th style="padding: 10px;">Valor</th><th style="padding: 10px; width: 350px;">Ação: Inserir LF</th>
            </tr>
            <?php foreach($itens_lf as $i): ?>
            <tr style="border-bottom: 1px solid #eee; <?= $i['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                <td style="padding: 10px;"><b><?= htmlspecialchars($i['numero_geral']) ?></b><br>NF: <?= htmlspecialchars($i['num_documento_fiscal']) ?></td>
                <td style="padding: 10px; font-family: monospace; color: #004488; font-weight: bold; font-size: 1.1em;"><?= htmlspecialchars($i['np_numero']) ?></td>
                <td style="padding: 10px; color: #28a745; font-weight: bold;">R$ <?= number_format($i['valor_total'], 2, ',', '.') ?></td>
                <td style="padding: 10px;">
                    <form action="/operador/acao" method="POST" style="display: flex; gap: 5px;">
                        <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                        <input type="hidden" name="tipo_acao" value="inserir_lf">
                        <input type="text" name="lf_numero" placeholder="Ex: 2026LF000456" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex: 1;">
                        <button type="submit" style="background: #17a2b8; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; cursor: pointer;">Salvar LF</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<script>
function openTab(tabName) {
    var i;
    var x = document.getElementsByClassName("tab-content");
    for (i = 0; i < x.length; i++) { x[i].style.display = "none"; }
    var btns = document.getElementsByClassName("tab-btn");
    for (i = 0; i < btns.length; i++) { 
        btns[i].style.background = "#e9ecef"; 
        btns[i].style.color = "#333"; 
    }
    document.getElementById(tabName).style.display = "block";
    document.getElementById("btn-" + tabName).style.background = "#004488";
    document.getElementById("btn-" + tabName).style.color = "white";
}
function mostrarRejeicao(id) {
    var form = document.getElementById('form-rej-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>