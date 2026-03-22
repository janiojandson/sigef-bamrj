<?php $page_title = 'Relatório de OB - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">📊 Relatório de Ordens Bancárias (Liquidadas)</h2>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
    <form action="/relatorio/ob" method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <div>
            <label style="font-weight: bold; font-size: 0.9em;">Data Inicial:</label><br>
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div>
            <label style="font-weight: bold; font-size: 0.9em;">Data Final:</label><br>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <button type="submit" style="background: #6f42c1; color: white; border: none; padding: 9px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Gerar Relatório</button>
    </form>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <?php if(empty($relatorio)): ?>
        <p style="text-align: center; color: #666; padding: 30px 0;">Nenhum pagamento liquidado neste período.</p>
    <?php else: ?>
        <h3 style="color: #28a745; margin-top: 0;">Valor Total Pago no Período: R$ <?= number_format($valor_total_periodo, 2, ',', '.') ?></h3>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding: 10px;">Data PGTO</th><th style="padding: 10px;">Número OB</th><th style="padding: 10px;">DE Origem</th><th style="padding: 10px;">CNPJ/Doc</th><th style="padding: 10px;">Valor Pago</th>
                </tr>
                <?php foreach($relatorio as $r): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><b><?= date('d/m/Y', strtotime($r['data_pagamento'])) ?></b></td>
                    <td style="padding: 10px; font-family: monospace; font-size: 1.1em; color: #6f42c1;"><b><?= htmlspecialchars($r['ob_numero']) ?></b></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($r['numero_geral']) ?> <small>(<?= htmlspecialchars($r['origem_tipo']) ?>)</small></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($r['cpf_cnpj']) ?><br>NF: <?= htmlspecialchars($r['num_documento_fiscal']) ?></td>
                    <td style="padding: 10px; color: #28a745; font-weight: bold;">R$ <?= number_format($r['valor_total'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>