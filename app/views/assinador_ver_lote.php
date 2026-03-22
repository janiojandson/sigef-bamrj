<?php $page_title = 'Aprovação de Pagamento - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">✍️ Assinatura de Lote: <code style="color: #d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></code></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Origem: <b><?= htmlspecialchars($lote['origem_tipo']) ?></b></p>
    </div>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #6f42c1;">
    <h3 style="margin-top: 0; color: #333;">Itens Aguardando Sua Assinatura</h3>
    
    <?php if (empty($itens)): ?>
        <p style="color: #28a745; font-weight: bold; text-align: center; padding: 30px;">✅ Todos os itens deste lote já foram processados por você.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 12px;">Prioridade</th>
                    <th style="padding: 12px;">Doc / Fornecedor</th>
                    <th style="padding: 12px; color: #6f42c1;">Ordem de Pagamento (OP)</th>
                    <th style="padding: 12px;">Valor (R$)</th>
                    <th style="padding: 12px; text-align: right;">Ação Oficial</th>
                </tr>
                <?php foreach ($itens as $item): ?>
                <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                    <td style="padding: 12px; text-align: center; font-size: 1.2em;"><?= $item['prioridade'] ? '🚩' : '🏳️' ?></td>
                    <td style="padding: 12px;">
                        NF: <b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b><br>
                        <small>CNPJ: <?= htmlspecialchars($item['cpf_cnpj']) ?></small>
                    </td>
                    
                    <td style="padding: 12px;">
                        <code style="font-size: 1.3em; color: #6f42c1; background: #f3f0f7; padding: 4px 8px; border-radius: 4px; border: 1px solid #d8cde9;">
                            <?= htmlspecialchars($item['op_numero']) ?>
                        </code>
                    </td>
                    
                    <td style="padding: 12px; color: #28a745; font-weight: bold; font-size: 1.1em;">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></td>
                    
                    <td style="padding: 12px; text-align: right;">
                        <div style="display: flex; gap: 5px; justify-content: flex-end;">
                            <form action="/assinador/acao" method="POST" style="margin: 0;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                <input type="hidden" name="acao" value="aprovar">
                                <button type="submit" style="background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;">✅ Aprovar</button>
                            </form>

                            <form action="/assinador/acao" method="POST" style="margin: 0; display: flex; gap: 5px;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                <input type="hidden" name="acao" value="rejeitar">
                                <input type="text" name="observacao" placeholder="Motivo da recusa" required style="padding: 8px; border: 1px solid #dc3545; border-radius: 4px; width: 140px; font-size: 0.85em;">
                                <button type="submit" onclick="return confirm('Deseja devolver este item para a Execução Financeira?')" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;">❌ Rejeitar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>