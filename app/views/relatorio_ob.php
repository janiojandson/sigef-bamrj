<?php $page_title = 'Relatório de OBs - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">📊 Relatório de OBs Liquidadas</h2>
    <a href="/" class="btn btn-secondary" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Dashboard</a>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #28a745;">
    
    <form action="/relatorio/ob" method="GET" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: flex-end; background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; flex-wrap: wrap;">
        <div>
            <label style="font-weight: bold; color: #555; font-size: 0.9em;">Data PGT Inicial</label><br>
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($_GET['data_inicio'] ?? date('Y-m-01')) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div>
            <label style="font-weight: bold; color: #555; font-size: 0.9em;">Data PGT Final</label><br>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? date('Y-m-t')) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 8px 20px; background: #004488; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🔍 Filtrar</button>
        
        <?php if (!empty($obs)): ?>
            <button type="button" onclick="gerarDossiePDF()" class="btn btn-success" style="padding: 8px 20px; margin-left: auto; font-weight: bold; font-size: 1em; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">📑 Gerar Dossiê Único (PDF)</button>
        <?php endif; ?>
    </form>

    <?php if (empty($obs)): ?>
        <p style="text-align: center; color: #666; padding: 20px; font-weight: bold; font-size: 1.2em;">Nenhuma Ordem Bancária encontrada neste período.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <tr style="background: #e9ecef; border-bottom: 2px solid #ccc; text-align: left;">
                    <th style="padding: 12px;">Nº OB</th>
                    <th style="padding: 12px;">Data PGT</th>
                    <th style="padding: 12px;">NP / OP</th>
                    <th style="padding: 12px;">ID / NF</th>
                    <th style="padding: 12px; text-align: center;">Comprovante</th>
                </tr>
                <?php foreach ($obs as $ob): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px; font-weight: bold; color: #004488; font-size: 1.2em;"><?= htmlspecialchars($ob['ob_numero']) ?></td>
                    <td style="padding: 12px;"><b><?= date('d/m/Y', strtotime($ob['data_pagamento'])) ?></b></td>
                    <td style="padding: 12px;">NP: <b><?= htmlspecialchars($ob['np_numero']) ?></b><br>OP: <b><?= htmlspecialchars($ob['op_numero']) ?></b></td>
                    <td style="padding: 12px;">
                        <span style="background:#333; color:white; padding:2px 5px; border-radius:3px; font-family:monospace;">#<?= str_pad($ob['id'], 5, '0', STR_PAD_LEFT) ?></span><br>
                        NF: <?= htmlspecialchars($ob['num_documento_fiscal']) ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <?php if (!empty($ob['ob_arquivo'])): ?>
                            <a href="<?= htmlspecialchars($ob['ob_arquivo']) ?>" target="_blank" class="btn btn-info ob-link-arquivo" data-url="<?= htmlspecialchars($ob['ob_arquivo']) ?>" style="background: #17a2b8; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">📥 Abrir PDF</a>
                        <?php else: ?>
                            <span style="color: #dc3545; font-size: 0.85em; font-weight: bold;">S/ Arquivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>

<script>
async function gerarDossiePDF() {
    try {
        const links = document.querySelectorAll('.ob-link-arquivo');
        if (links.length === 0) {
            alert('Não há PDFs de comprovantes válidos nesta página para gerar o dossiê.');
            return;
        }

        alert('Processando Dossiê... Isso vai unir todos os ' + links.length + ' PDFs mostrados na tela. Aguarde...');

        const { PDFDocument } = PDFLib;
        const mergedPdf = await PDFDocument.create();

        for (let i = 0; i < links.length; i++) {
            const url = links[i].getAttribute('data-url');
            try {
                const response = await fetch(url);
                const arrayBuffer = await response.arrayBuffer();
                const pdfDoc = await PDFDocument.load(arrayBuffer);
                const copiedPages = await mergedPdf.copyPages(pdfDoc, pdfDoc.getPageIndices());
                copiedPages.forEach((page) => mergedPdf.addPage(page));
            } catch (err) {
                console.error('Erro ao ler PDF: ' + url, err);
            }
        }

        const pdfBytes = await mergedPdf.save();
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        const urlBlob = URL.createObjectURL(blob);
        window.open(urlBlob, '_blank');

    } catch (error) {
        console.error(error);
        alert('Falha ao gerar dossiê. Ocorreu um erro no processamento interno dos PDFs.');
    }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
