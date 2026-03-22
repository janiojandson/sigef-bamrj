<?php $page_title = 'Conferência do Lote - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📂 Conferência de Lote: <code style="color: #d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></code></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Origem: <b><?= htmlspecialchars($lote['origem_tipo']) ?></b></p>
    </div>
    <a href="/protocolo/fila" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar à Fila</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">📦 Itens da DE</h3>
    
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                <th style="padding: 12px; width: 50px;">Prior.</th>
                <th style="padding: 12px;">CNPJ / CPF / PA</th>
                <th style="padding: 12px;">Nº Documento</th>
                <th style="padding: 12px;">Valor (R$)</th>
                <th style="padding: 12px;">Status</th>
                <th style="padding: 12px; text-align: right;">Decisão (Físico)</th>
            </tr>
            <?php foreach ($itens as $item): ?>
            <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                <td style="padding: 12px; text-align: center; font-size: 1.2em;"><?= $item['prioridade'] ? '🚩' : '🏳️' ?></td>
                <td style="padding: 12px;">
                    <?= htmlspecialchars($item['cpf_cnpj']) ?>
                    <?php if (!empty($item['pa_numero'])): ?>
                        <br><span style="background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold; margin-top:4px; display:inline-block;">PA: <?= htmlspecialchars($item['pa_numero']) ?></span>
                    <?php endif; ?>
                </td>
                <td style="padding: 12px;"><b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b></td>
                <td style="padding: 12px; color: #28a745; font-weight: bold;">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></td>
                <td style="padding: 12px;">
                    <span style="font-size: 0.85em; padding: 4px 8px; border-radius: 4px; background: <?= $item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' ? '#ffeeba' : '#d4edda' ?>;">
                        <?= htmlspecialchars($item['status_atual']) ?>
                    </span>
                </td>
                
                <td style="padding: 12px; text-align: right;">
                    <?php if ($item['status_atual'] === 'AGUARDANDO_RECEBIMENTO_PROTOCOLO'): ?>
                        <div style="display: flex; gap: 5px; justify-content: flex-end;">
                            <form action="/protocolo/receber" method="POST" style="margin: 0;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                <button type="submit" style="background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;" title="Aceitar Documento">✅ Receber</button>
                            </form>

                            <form action="/protocolo/rejeitar" method="POST" style="margin: 0; display: flex; gap: 5px;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                <input type="text" name="observacao" placeholder="Motivo da Rejeição" required style="padding: 8px; border: 1px solid #dc3545; border-radius: 4px; width: 140px; font-size: 0.85em;">
                                <button type="submit" onclick="return confirm('Deseja REJEITAR e devolver este item?')" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;" title="Devolver à Origem">❌ Rejeitar</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <span style="color: #666; font-size: 0.9em; font-weight: bold;">Já Processado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>