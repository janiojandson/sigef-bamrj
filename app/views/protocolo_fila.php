<?php $page_title = 'Fila do Protocolo - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">📥 Fila do Protocolo (Lotes Pendentes)</h2>
    <a href="/" class="btn btn-secondary">⬅️ Dashboard</a>
</div>

<!-- 🔍 Filtro em Tempo Real -->
<div style="margin-bottom: 20px;">
    <input type="text" id="filtroProtocolo" class="filtro-real" placeholder="🔍 Filtrar por DE, Origem ou Data..." onkeyup="filtrarTabela()">
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #17a2b8;">
    <?php if (empty($lotes_pendentes)): ?>
        <h3 style="text-align: center; color: #28a745; padding: 40px 0;">✅ Nenhuma DE aguardando recebimento físico.</h3>
    <?php else: ?>
        <div class="table-responsive">
            <table id="tabelaProtocolo" style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding: 12px; color: #002244;">DE / Lote</th>
                    <th style="padding: 12px; color: #002244;">Origem</th>
                    <th style="padding: 12px; color: #002244;">Data de Envio</th>
                    <th style="padding: 12px; text-align: right;">Ações</th>
                </tr>
                <?php foreach ($lotes_pendentes as $lote): ?>
                <tr class="linha-filtravel" style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><code style="color: #d32f2f; font-weight: bold; font-size: 1.1em;"><?= htmlspecialchars($lote['numero_geral']) ?></code></td>
                    <td style="padding: 12px;"><b><?= htmlspecialchars($lote['origem_tipo']) ?></b> (<?= htmlspecialchars($lote['criado_por']) ?>)</td>
                    <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></td>
                    <td style="padding: 12px; text-align: right; white-space: nowrap;">
                        <a href="/protocolo/imprimir_capa?id=<?= $lote['id'] ?>" target="_blank" class="btn btn-warning" style="font-size: 0.85em;">🖨️ Capa</a>
                        <a href="/protocolo/lote?id=<?= $lote['id'] ?>" class="btn btn-primary" style="font-size: 0.85em;">📂 Abrir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function filtrarTabela() {
    const termo = document.getElementById('filtroProtocolo').value.toLowerCase();
    const linhas = document.querySelectorAll('.linha-filtravel');
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(termo) ? '' : 'none';
    });
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>