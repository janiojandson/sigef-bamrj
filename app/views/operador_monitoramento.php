<?php $page_title = 'Monitoramento Global - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📊 Monitoramento Global da Execução</h2>
        <p style="margin: 5px 0 0 0; color: #666;">Visão tática de todos os processos ativos pós-protocolo.</p>
    </div>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <?php if (empty($itens_ativos)): ?>
        <p style="color: #28a745; font-weight: bold; text-align: center; padding: 30px;">✅ Nenhuma nota ativa transitando na máquina de estados no momento.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px; font-size: 0.9em;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding: 12px;">Data Envio / DE</th>
                    <th style="padding: 12px;">CNPJ / Doc</th>
                    <th style="padding: 12px;">Valor</th>
                    <th style="padding: 12px;">Dados Sistêmicos</th>
                    <th style="padding: 12px; color: #004488;">Posição / Fila Atual</th>
                </tr>
                <?php foreach ($itens_ativos as $i): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">
                        <b><?= htmlspecialchars($i['numero_geral']) ?></b><br>
                        <small style="color: #666;"><?= htmlspecialchars($i['origem_tipo']) ?></small>
                    </td>
                    <td style="padding: 12px;">
                        <?= htmlspecialchars($i['cpf_cnpj']) ?><br>
                        NF: <b><?= htmlspecialchars($i['num_documento_fiscal']) ?></b>
                    </td>
                    <td style="padding: 12px; font-weight: bold; color: #28a745;">R$ <?= number_format($i['valor_total'], 2, ',', '.') ?></td>
                    <td style="padding: 12px; line-height: 1.4;">
                        <?php if($i['pa_numero']) echo "PA: {$i['pa_numero']}<br>"; ?>
                        <?php if($i['np_numero']) echo "NP: {$i['np_numero']}<br>"; ?>
                        <?php if($i['lf_numero']) echo "LF: {$i['lf_numero']}<br>"; ?>
                        <?php if($i['op_numero']) echo "OP: <b style='color:#6f42c1'>{$i['op_numero']}</b>"; ?>
                    </td>
                    <td style="padding: 12px;">
                        <span style="background: #e2e3e5; color: #002244; font-weight: bold; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                            <?= str_replace('_', ' ', htmlspecialchars($i['status_atual'])) ?>
                        </span>
                        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">
                            Última Ação: <?= htmlspecialchars(explode(':', $i['observacao_atual'])[1] ?? 'Avanço de fase') ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>