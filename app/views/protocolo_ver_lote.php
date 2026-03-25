<?php $page_title = 'Conferência do Lote - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📂 Conferência de Lote: <code style="color: #d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></code></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Origem: <b><?= htmlspecialchars($lote['origem_tipo']) ?></b></p>
    </div>
    <a href="/protocolo/fila" class="btn btn-secondary">⬅️ Voltar à Fila</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    
    <form action="/protocolo/receber" method="POST" id="form-protocolo">
        <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
        
        <div style="margin-bottom: 15px; padding: 15px; background: #e2e3e5; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <b style="color: #383d41;">Selecione os documentos físicos que estão corretos:</b>
            <button type="submit" class="btn btn-success">✅ Dar Entrada na Base (Selecionados)</button>
        </div>

        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 12px; width: 40px; text-align: center;">
                        <input type="checkbox" id="checkAll" onclick="toggleCheckboxes(this)" style="transform: scale(1.3); cursor: pointer;" checked>
                    </th>
                    <th style="padding: 12px; width: 50px;">Prior.</th>
                    <th style="padding: 12px;">ID do Item</th>
                    <th style="padding: 12px;">CNPJ / CPF / NS</th>
                    <th style="padding: 12px;">Nº Documento (NF)</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px; text-align: right;">Devolução (Falha Física)</th>
                </tr>
                <?php foreach ($itens as $item): 
                    $is_rejeitado = str_contains($item['status_atual'] ?? '', 'REJEITADO');
                ?>
                <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                    
                    <?php if ($item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_PROTOCOLO'): ?>
                        <td style="padding: 12px; text-align: center;">
                            <input type="checkbox" name="itens_selecionados[]" value="<?= $item['id'] ?>" class="item-checkbox" style="transform: scale(1.3); cursor: pointer;" checked>
                        </td>
                    <?php else: ?>
                        <td style="padding: 12px; text-align: center; color: #28a745;">✔️</td>
                    <?php endif; ?>
                    
                    <td style="padding: 12px; text-align: center; font-size: 1.2em;"><?= $item['prioridade'] ? '🚩' : '🏳️' ?></td>
                    
                    <td style="padding: 12px;">
                        <span style="background: #002244; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-family: monospace; font-size: 1.1em; border: 1px solid #001122;">
                            #<?= str_pad($item['id'], 5, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?php if ($is_rejeitado): ?>
                            <br><span style="display: inline-block; background: #dc3545; color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; margin-top: 5px;">🚨 REJEITADO</span>
                        <?php endif; ?>
                    </td>

                    <td style="padding: 12px;">
                        <?= htmlspecialchars($item['cpf_cnpj']) ?>
                        <?php if (!empty($item['ns_numero'])): ?>
                            <br><span style="background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold; margin-top:4px; display:inline-block;">NS: <?= htmlspecialchars($item['ns_numero']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px;"><b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b></td>
                    <td style="padding: 12px;">
                        <span class="badge" style="background: <?= $item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' ? '#ffeeba; color:#856404;' : '#d4edda; color:#155724;' ?>;">
                            <?= htmlspecialchars($item['status_atual']) ?>
                        </span>
                    </td>
                    
                    <td style="padding: 12px; text-align: right;">
                        <?php if ($item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_PROTOCOLO'): ?>
                            <button type="button" onclick="document.getElementById('rej-<?= $item['id'] ?>').style.display='flex';" class="btn btn-outline-danger" style="padding: 4px 8px; font-size: 0.85em;">❌ Rejeitar Físico</button>
                        <?php else: ?>
                            <span style="color: #666; font-size: 0.9em; font-weight: bold;">Já Processado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <?php if ($item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_PROTOCOLO'): ?>
                <tr id="rej-<?= $item['id'] ?>" style="display: none; background: #fff3cd;">
                    <td colspan="7" style="padding: 10px; text-align: right; border-bottom: 2px solid #ffeeba;">
                        <div style="display: flex; gap: 5px; justify-content: flex-end; align-items: center;">
                            <b style="color: #856404; font-size: 0.9em;">Motivo da Devolução:</b>
                            <input type="text" form="form-rej-<?= $item['id'] ?>" name="observacao" required placeholder="Falta carimbo, rasgado..." style="padding: 6px; border: 1px solid #dc3545; border-radius: 4px; width: 250px;">
                            <button type="submit" form="form-rej-<?= $item['id'] ?>" class="btn btn-danger" style="padding: 6px 12px;">Confirmar Rejeição</button>
                            <button type="button" onclick="document.getElementById('rej-<?= $item['id'] ?>').style.display='none';" class="btn btn-secondary" style="padding: 6px 12px;">Cancelar</button>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php endforeach; ?>
            </table>
        </div>
    </form>

    <?php foreach ($itens as $item): ?>
        <form action="/protocolo/rejeitar" method="POST" id="form-rej-<?= $item['id'] ?>" style="display: none;">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
        </form>
    <?php endforeach; ?>

</div>

<script>
function toggleCheckboxes(source) {
    checkboxes = document.getElementsByClassName('item-checkbox');
    for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; }
}
document.getElementById('form-protocolo').addEventListener('submit', function(e) {
    var checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) { e.preventDefault(); alert('Selecione pelo menos um documento para receber na Base.'); }
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
