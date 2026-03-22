<?php $page_title = 'Acompanhamento - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">🔍 Rastreio do Lote: <code style="color: #d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></code></h2>
        <p style="margin: 5px 0 0 0; color: #666;">Enviado em: <b><?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></b></p>
    </div>
    <button onclick="history.back()" style="background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">⬅️ Voltar</button>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                <th style="padding: 12px;">CNPJ/Doc</th><th style="padding: 12px;">Valor (R$)</th><th style="padding: 12px;">Fase Atual</th><th style="padding: 12px;">Última Observação</th>
            </tr>
            <?php foreach ($itens as $item): 
                $is_rejeitado = str_contains($item['status_atual'], 'REJEITADO');
                $is_cancelado = str_contains($item['status_atual'], 'CANCELADO_PELA_ORIGEM');
            ?>
            <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                <td style="padding: 12px; <?= $is_cancelado ? 'text-decoration: line-through; color: #aaa;' : '' ?>">
                    <?= htmlspecialchars($item['cpf_cnpj']) ?><br><b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b> <?= $item['prioridade'] ? '🚩' : '' ?>
                </td>
                <td style="padding: 12px; color: <?= $is_cancelado ? '#aaa' : '#28a745' ?>; font-weight: bold;">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></td>
                
                <td style="padding: 12px;">
                    <span style="font-size: 0.8em; padding: 5px 8px; border-radius: 4px; font-weight: bold; <?= $is_rejeitado ? 'background: #dc3545; color: white;' : ($is_cancelado ? 'background: #666; color: white;' : 'background: #e2e3e5; color: #002244;') ?>">
                        <?= htmlspecialchars($item['status_atual']) ?>
                    </span>
                    
                    <?php if ($is_rejeitado && in_array($_SESSION['role'], ['OMAP', 'Setor_BAMRJ'])): ?>
                        <div style="margin-top: 15px; padding: 10px; border: 1px dashed #dc3545; border-radius: 4px; background: #fff5f5;">
                            <form action="/de/reenviar" method="POST" style="margin-bottom: 5px; display: flex; gap: 5px;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                <input type="text" name="observacao" required placeholder="O que corrigiu?" style="padding: 6px; border: 1px solid #ccc; border-radius: 4px; flex: 1; font-size: 0.85em;">
                                <button type="submit" style="background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.85em;">🔄 Reenviar</button>
                            </form>
                            <form action="/de/excluir_item" method="POST" onsubmit="return confirm('ATENÇÃO: Deseja cancelar e excluir definitivamente esta nota do sistema?')" style="display: flex; justify-content: flex-end;">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                <button type="submit" style="background: transparent; color: #dc3545; border: 1px solid #dc3545; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.8em;">🗑️ Cancelar/Excluir Definitivo</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </td>
                
                <td style="padding: 12px; font-size: 0.85em; color: <?= $is_rejeitado ? '#dc3545' : ($is_cancelado ? '#aaa' : '#555') ?>; font-weight: <?= $is_rejeitado ? 'bold' : 'normal' ?>;">
                    <?= htmlspecialchars($item['observacao_atual']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>