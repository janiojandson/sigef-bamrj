<?php
$page_title = 'Fila do Protocolo - SIGEF';
require __DIR__ . '/partials/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📥 Fila de Trabalho: Setor de Protocolo</h2>
        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Aceite físico e digital dos itens encaminhados.</p>
    </div>
    <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #17a2b8;">
    
    <?php if (empty($itens_pendentes)): ?>
        <div style="text-align: center; padding: 40px 0;">
            <h3 style="color: #28a745;">✅ Fila Limpa!</h3>
            <p style="color: #666;">Nenhum documento aguardando recebimento no momento.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                        <th style="padding: 12px; color: #002244; width: 50px;">Prior.</th>
                        <th style="padding: 12px; color: #002244;">Lote (DE) / Origem</th>
                        <th style="padding: 12px; color: #002244;">CNPJ/CPF</th>
                        <th style="padding: 12px; color: #002244;">Nº Documento</th>
                        <th style="padding: 12px; color: #002244;">Valor (R$)</th>
                        <th style="padding: 12px; text-align: right; color: #002244;">Ação Tática</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens_pendentes as $item): ?>
                    <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                        <td style="padding: 12px; text-align: center;"><?= $item['prioridade'] ? '🚩' : '🏳️' ?></td>
                        <td style="padding: 12px;">
                            <code style="color: #d32f2f; font-weight: bold; font-size: 1.1em;"><?= htmlspecialchars($item['numero_geral']) ?></code><br>
                            <small style="color: #555;">Origem: <b><?= htmlspecialchars($item['origem_tipo']) ?></b></small>
                        </td>
                        <td style="padding: 12px;"><?= htmlspecialchars($item['cpf_cnpj']) ?></td>
                        <td style="padding: 12px;"><b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b></td>
                        <td style="padding: 12px; color: #28a745; font-weight: bold;">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></td>
                        
                        <td style="padding: 12px; text-align: right;">
                            <form action="/protocolo/receber" method="POST" style="margin: 0; display: inline-flex; gap: 5px; align-items: center;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="text" name="observacao" placeholder="Obs (Opcional)" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 150px; font-size: 0.85em;">
                                <button type="submit" style="background: #004488; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    📥 RECEBER FÍSICO
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>