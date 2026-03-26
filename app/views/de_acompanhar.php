<?php $page_title = 'Rastreador de Itens - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">🔍 Rastreador de Itens</h2>
        <p style="margin: 5px 0 0 0; color: #666;">Documento de Encaminhamento Base: <b style="color:#d32f2f;"><?= htmlspecialchars($lote['numero_geral']) ?></b></p>
    </div>
    <button onclick="history.back()" class="btn btn-secondary">⬅️ Voltar</button>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #004488;">
    <h3 style="margin-top:0; border-bottom: 2px solid #eee; padding-bottom: 10px;">📦 Itens Contidos nesta DE</h3>
    
    <?php foreach ($itens as $item): 
        $is_rejeitado = str_contains($item['status_atual'], 'REJEITADO') || str_contains($item['observacao_atual'] ?? '', 'DEVOLVIDO');
        $is_cancelado = str_contains($item['status_atual'], 'CANCELADO') || str_contains($item['status_atual'], 'CANCELAMENTO');
    ?>
        <div style="border: 1px solid #ccc; border-radius: 6px; margin-bottom: 15px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            
            <div style="background: <?= $is_rejeitado ? '#fff5f5' : '#f8f9fa' ?>; padding: 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="document.getElementById('hist-<?= $item['id'] ?>').style.display = document.getElementById('hist-<?= $item['id'] ?>').style.display === 'none' ? 'block' : 'none';">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <span style="background: #002244; color: white; padding: 5px 10px; border-radius: 4px; font-family: monospace; font-size: 1.2em;">#<?= str_pad($item['id'], 5, '0', STR_PAD_LEFT) ?></span>
                    <div>
                        <b style="font-size: 1.1em; <?= $is_cancelado ? 'text-decoration: line-through; color: #aaa;' : '' ?>">NF: <?= htmlspecialchars($item['num_documento_fiscal']) ?></b> <?= $item['prioridade'] ? '🚩' : '' ?><br>
                        <small style="color: #666;">CNPJ: <?= htmlspecialchars($item['cpf_cnpj']) ?></small>
                        <?php if (!empty($item['ns_numero'])): ?>
                            <span style="margin-left: 10px; background:#ffcc00; color:#002244; padding:2px 6px; border-radius:4px; font-size:0.85em; font-weight:bold;">📌 NS: <?= htmlspecialchars($item['ns_numero']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.85em; padding: 5px 10px; border-radius: 4px; font-weight: bold; <?= $is_rejeitado ? 'background: #dc3545; color: white;' : ($is_cancelado ? 'background: #666; color: white;' : 'background: #e2e3e5; color: #002244;') ?>">
                        <?= str_replace('_', ' ', htmlspecialchars($item['status_atual'])) ?>
                    </span>
                    <br><small style="color: #004488; font-weight: bold; display: inline-block; margin-top: 5px;">🔽 Ver Histórico</small>
                </div>
            </div>

            <div id="hist-<?= $item['id'] ?>" style="display: none; padding: 20px; background: #fff; border-top: 1px solid #eee;">
                
                <div style="background: #e9ecef; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-family: monospace; font-size: 0.9em; display:flex; gap: 15px; border-left: 4px solid #17a2b8;">
                    <span><b>NP:</b> <?= $item['np_numero'] ?: '---' ?></span>
                    <span><b>LF:</b> <?= $item['lf_numero'] ?: '---' ?></span>
                    <span><b>OP:</b> <?= $item['op_numero'] ?: '---' ?></span>
                </div>

                <?php if ($is_rejeitado && in_array($_SESSION['role'], ['OMAP', 'Setor_BAMRJ'])): ?>
                    <div style="margin-bottom: 20px; padding: 15px; border: 1px dashed #dc3545; border-radius: 4px; background: #fffafb;">
                        <h4 style="margin: 0 0 10px 0; color: #dc3545;">⚠️ Ação Necessária (Correção)</h4>
                        <form action="/de/reenviar" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                            <input type="text" name="num_doc" value="<?= htmlspecialchars($item['num_documento_fiscal']) ?>" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 120px;" placeholder="Nº Doc">
                            <input type="text" name="ns_numero" value="<?= htmlspecialchars($item['ns_numero'] ?? '') ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 120px;" placeholder="Nº NS">
                            <input type="text" name="observacao" required placeholder="O que foi corrigido?" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex: 1; min-width: 200px;">
                            <button type="submit" class="btn btn-success" style="padding: 8px 15px; font-weight: bold;">🔄 Corrigir e Reenviar</button>
                        </form>
                    </div>
                <?php endif; ?>

                <h4 style="margin: 0 0 10px 0; color: #555;">Linha do Tempo de Auditoria</h4>
                <div style="border-left: 3px solid #004488; padding-left: 15px; margin-left: 10px;">
                    <?php 
                    $db = \App\Core\Database::getConnection();
                    $stmtEv = $db->prepare("SELECT * FROM de_eventos WHERE item_id = ? ORDER BY criado_em ASC");
                    $stmtEv->execute([$item['id']]);
                    $eventos = $stmtEv->fetchAll();
                    
                    if (empty($eventos)): ?>
                        <p style="color: #999;">Nenhum trâmite registrado.</p>
                    <?php else: ?>
                        <?php foreach ($eventos as $ev): ?>
                            <div style="margin-bottom: 15px; position: relative;">
                                <div style="position: absolute; left: -24px; top: 0; background: #004488; width: 10px; height: 10px; border-radius: 50%;"></div>
                                <small style="color: #666; font-weight: bold;"><?= date('d/m/Y H:i', strtotime($ev['criado_em'])) ?></small><br>
                                <b style="color: #002244;"><?= htmlspecialchars($ev['acao']) ?></b> por <span style="color: #004488;"><?= htmlspecialchars($ev['usuario_nip']) ?> (<?= htmlspecialchars($ev['perfil_atuante']) ?>)</span><br>
                                <?php if (!empty($ev['justificativa'])): ?>
                                    <div style="background: #fdfdfe; border-left: 3px solid #ccc; padding: 5px 10px; margin-top: 5px; color: #555; font-style: italic; font-size: 0.9em;">
                                        "<?= htmlspecialchars($ev['justificativa']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
