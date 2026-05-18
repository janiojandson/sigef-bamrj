<?php $page_title = 'Monitoramento Global - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📊 Monitoramento Global e RAPs</h2>
        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">🧹 Apenas processos ativos — ARQUIVADOS e CANCELADOS não aparecem</p>
    </div>
    <div>
        <?php if ($_SESSION['role'] === 'Operador'): ?>
        <a href="/relatorio/ob" class="btn btn-success" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">🏦 Relatório de OBs</a>
        <?php endif; ?>
        <a href="/" class="btn btn-secondary" style="margin-left: 5px;">⬅️ Dashboard</a>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; border-left: 5px solid #ffcc00;">
    <h3 style="margin-top: 0; color: #333;">🖨️ RAPs Ativos (Prontos para Impressão)</h3>
    <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px;">
        <?php if(!empty($raps)): foreach($raps as $rap): ?>
            <div style="background: #f8f9fa; border: 1px solid #ccc; padding: 10px; border-radius: 4px; display: flex; flex-direction: column; align-items: center; min-width: 150px;">
                <a href="/operador/imprimir_rap?id=<?= $rap['id'] ?>" target="_blank" style="text-decoration: none; color: #004488; font-weight: bold; text-align: center; margin-bottom: 8px;">
                    📄 <?= htmlspecialchars($rap['numero_rap']) ?><br>
                    <small style="color: #666;"><?= date('d/m', strtotime($rap['criado_em'])) ?></small>
                </a>
                <?php if ($_SESSION['role'] === 'Operador'): ?>
                <a href="/operador/excluir_rap?id=<?= $rap['id'] ?>" onclick="return confirm('Deseja cancelar este RAP? Apenas as OPs que AINDA NÃO foram assinadas voltarão para sua fila de geração. OPs que já avançaram não serão afetadas.')" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8em; text-decoration: none;">❌ Cancelar RAP</a>
                <?php endif; ?>
            </div>
        <?php endforeach; else: ?>
            <span style="color: #666;">Nenhum RAP ativo. Todos foram arquivados ou cancelados.</span>
        <?php endif; ?>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="margin-bottom: 15px;">
        <input type="text" id="filtroMonitoramento" onkeyup="filtrarTabela()" placeholder="🔍 Filtrar por ID, CNPJ, DE, OP ou Status..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em;">
    </div>

    <div class="table-responsive">
        <table id="tabelaMonitoramento" style="width: 100%; border-collapse: collapse; min-width: 900px; font-size: 0.9em;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                <th style="padding: 12px;">ID / Origem (DE)</th>
                <th style="padding: 12px;">CNPJ / Doc / NS</th>
                <th style="padding: 12px;">Dados Sistêmicos</th>
                <th style="padding: 12px;">RAP</th>
                <th style="padding: 12px;">Status</th>
                <th style="padding: 12px;">Prioridade</th>
            </tr>
            <?php foreach ($todos_itens as $item): ?>
            <tr style="border-bottom: 1px solid #eee;" class="linha-monitoramento">
                <td style="padding: 10px;">
                    <b>#<?= str_pad($item['id'], 5, '0', STR_PAD_LEFT) ?></b><br>
                    <small style="color: #666;"><?= htmlspecialchars($item['numero_geral'] ?? '') ?></small>
                </td>
                <td style="padding: 10px;">
                    <small><?= htmlspecialchars($item['cpf_cnpj'] ?? '') ?></small><br>
                    <b><?= htmlspecialchars($item['empresa_nome'] ?? 'Não Informado') ?></b><br>
                    <small>NF: <?= htmlspecialchars($item['num_documento_fiscal'] ?? '') ?></small>
                </td>
                <td style="padding: 10px;">
                    NS: <b><?= htmlspecialchars($item['ns_numero'] ?? '-') ?></b><br>
                    NP: <?= htmlspecialchars($item['np_numero'] ?? '-') ?><br>
                    LF: <?= htmlspecialchars($item['lf_numero'] ?? '-') ?><br>
                    OP: <b><?= htmlspecialchars($item['op_numero'] ?? '-') ?></b>
                </td>
                <td style="padding: 10px;">
                    <?php if (!empty($item['numero_rap'])): ?>
                        <a href="/operador/imprimir_rap?id=<?= $item['rap_id'] ?>" target="_blank" style="color: #004488; font-weight: bold; text-decoration: none;">
                            📄 <?= htmlspecialchars($item['numero_rap']) ?>
                        </a>
                    <?php else: ?>
                        <span style="color: #999;">—</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 10px; font-weight: bold; color: #004488; font-size: 0.85em;">
                    <?= str_replace('AGUARDANDO_', '', str_replace('AGU_', '', $item['status_atual'])) ?>
                </td>
                <td style="padding: 10px; text-align: center;">
                    <?= $item['prioridade'] ? '<span style="background:#dc3545; color:white; padding:2px 6px; border-radius:3px; font-size:0.8em;">🔴 URG</span>' : '<span style="color:#28a745;">Normal</span>' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function filtrarTabela() {
    const termo = document.getElementById('filtroMonitoramento').value.toLowerCase();
    document.querySelectorAll('.linha-monitoramento').forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(termo) ? '' : 'none';
    });
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>