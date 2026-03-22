<?php $page_title = 'Acompanhamento de DE - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">🔍 Rastreio do Lote: <code style="color: #d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></code></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Enviado em: <b><?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></b></p>
    </div>
    <button onclick="history.back()" style="background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">⬅️ Voltar</button>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">📦 Posição Atual dos Itens (Notas/Faturas)</h3>
    
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                <th style="padding: 12px;">CNPJ</th>
                <th style="padding: 12px;">Nº Documento</th>
                <th style="padding: 12px;">Valor (R$)</th>
                <th style="padding: 12px;">Fase Atual</th>
                <th style="padding: 12px;">Última Observação</th>
            </tr>
            <?php foreach ($itens as $item): ?>
            <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                <td style="padding: 12px;"><?= htmlspecialchars($item['cpf_cnpj']) ?></td>
                <td style="padding: 12px;"><b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b> <?= $item['prioridade'] ? '🚩' : '' ?></td>
                <td style="padding: 12px; color: #28a745; font-weight: bold;">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></td>
                <td style="padding: 12px;">
                    <span style="font-size: 0.8em; padding: 5px 8px; border-radius: 4px; background: #e2e3e5; color: #002244; font-weight: bold;">
                        <?= htmlspecialchars($item['status_atual']) ?>
                    </span>
                </td>
                <td style="padding: 12px; font-size: 0.85em; color: #555;">
                    <?= htmlspecialchars($item['observacao_atual']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>