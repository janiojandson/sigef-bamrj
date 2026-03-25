<?php $page_title = 'Aprovação de Pagamento - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">✍️ Assinatura de Lote: <code style="color: #d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></code></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Origem: <b><?= htmlspecialchars($lote['origem_tipo']) ?></b></p>
    </div>
    <a href="/" class="btn btn-secondary">⬅️ Dashboard</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #6f42c1;">
    <h3 style="margin-top: 0; color: #333;">Itens Aguardando Sua Assinatura</h3>
    
    <?php if (empty($itens)): ?>
        <p style="color: #28a745; font-weight: bold; text-align: center; padding: 30px;">✅ Todos os itens deste lote já foram processados por você.</p>
    <?php else: ?>
        <form action="/assinador/acao" method="POST" id="form-assinatura">
            <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
            
            <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; display: flex; gap: 10px; justify-content: flex-end; align-items: center; border: 1px solid #ddd;">
                <span style="font-weight: bold; color: #333; margin-right: auto;">Ações em Lote:</span>
                <input type="text" name="observacao" placeholder="Obs (Opcional p/ Aprovar, Obrigatório p/ Rejeitar)" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 300px;">
                <button type="submit" name="acao" value="aprovar" class="btn btn-success">✅ Aprovar Selecionados</button>
                <button type="submit" name="acao" value="rejeitar" class="btn btn-outline-danger" onclick="return confirm('Deseja REJEITAR e devolver os itens selecionados?')">❌ Rejeitar Selecionados</button>
            </div>

            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                        <th style="padding: 12px; width: 40px; text-align: center;">
                            <input type="checkbox" id="checkAll" onclick="toggleCheckboxes(this)" style="transform: scale(1.3); cursor: pointer;" checked>
                        </th>
                        <th style="padding: 12px;">Prioridade</th>
                        <th style="padding: 12px;">Doc / Fornecedor / NS</th>
                        <th style="padding: 12px; color: #6f42c1;">Ordem de Pagamento (OP)</th>
                    </tr>
                    <?php foreach ($itens as $item): ?>
                    <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                        <td style="padding: 12px; text-align: center;">
                            <input type="checkbox" name="itens_selecionados[]" value="<?= $item['id'] ?>" class="item-checkbox" style="transform: scale(1.3); cursor: pointer;" checked>
                        </td>
                        <td style="padding: 12px; text-align: center; font-size: 1.2em;"><?= $item['prioridade'] ? '🚩' : '🏳️' ?></td>
                        <td style="padding: 12px;">
                            NF: <b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b><br>
                            <small>CNPJ: <?= htmlspecialchars($item['cpf_cnpj']) ?></small>
                            <?php if (!empty($item['ns_numero'])): ?>
                                <br><span style="background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold; margin-top:4px; display:inline-block;">NS: <?= htmlspecialchars($item['ns_numero']) ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px;">
                            <code style="font-size: 1.3em; color: #6f42c1; background: #f3f0f7; padding: 4px 8px; border-radius: 4px; border: 1px solid #d8cde9;">
                                <?= htmlspecialchars($item['op_numero']) ?>
                            </code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleCheckboxes(source) {
    checkboxes = document.getElementsByClassName('item-checkbox');
    for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; }
}
document.getElementById('form-assinatura').addEventListener('submit', function(e) {
    var checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) { e.preventDefault(); alert('Selecione pelo menos um item para processar na tabela.'); }
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>