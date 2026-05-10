<?php $page_title = 'Histórico do Item'; require __DIR__ . '/partials/header.php'; ?>
<div style="max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #004488;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #002244;">📖 Histórico do Item #<?= str_pad($item['id'], 5, '0', STR_PAD_LEFT) ?></h2>
        <button onclick="window.close()" style="background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">❌ Fechar</button>
    </div>

    <div style="background: #e9ecef; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-family: monospace; font-size: 1em; display:flex; flex-wrap: wrap; gap: 15px; border-left: 4px solid #17a2b8;">
        <span><b>NF:</b> <?= htmlspecialchars($item['num_documento_fiscal']) ?></span>
        <span><b>CNPJ:</b> <?= htmlspecialchars($item['cpf_cnpj']) ?></span>
        <span><b>NP:</b> <?= $item['np_numero'] ?: '---' ?></span>
        <span><b>LF:</b> <?= $item['lf_numero'] ?: '---' ?></span>
        <span><b>OP:</b> <?= $item['op_numero'] ?: '---' ?></span>
        <span><b style="color:#28a745;">OB:</b> <?= $item['ob_numero'] ?: '---' ?></span>
        <?php if (!empty($item['ob_arquivo'])): 
            $arquivos = explode(',', $item['ob_arquivo']);
            foreach($arquivos as $idx => $arq):
        ?>
            <span><a href="/<?= htmlspecialchars(trim($arq)) ?>" target="_blank" style="background: #28a745; color: white; padding: 2px 8px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.9em;">📥 Baixar OB <?= count($arquivos)>1 ? ($idx+1) : '' ?></a></span>
        <?php endforeach; endif; ?>
    </div>

    <h4 style="margin: 0 0 15px 0; color: #555;">Linha do Tempo de Auditoria</h4>
    <div style="border-left: 3px solid #004488; padding-left: 20px; margin-left: 10px;">
        <?php if (empty($eventos)): ?>
            <p style="color: #999;">Nenhum trâmite registrado.</p>
        <?php else: ?>
            <?php foreach ($eventos as $ev): ?>
                <div style="margin-bottom: 20px; position: relative;">
                    <div style="position: absolute; left: -28px; top: 0; background: #004488; width: 12px; height: 12px; border-radius: 50%;"></div>
                    <small style="color: #666; font-weight: bold; font-size: 0.9em;"><?= date('d/m/Y H:i', strtotime($ev['timestamp'])) ?></small><br>
                    <b style="color: #002244; font-size: 1.1em;"><?= htmlspecialchars($ev['acao']) ?></b> por <span style="color: #004488;"><?= htmlspecialchars($ev['usuario_nip']) ?> (<?= htmlspecialchars($ev['perfil_atuante']) ?>)</span><br>
                    <?php if (!empty($ev['justificativa'])): ?>
                        <div style="background: #fdfdfe; border-left: 4px solid #ccc; padding: 8px 12px; margin-top: 5px; color: #555; font-style: italic;">
                            "<?= htmlspecialchars($ev['justificativa']) ?>"
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
