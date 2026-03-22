<?php $page_title = 'Relatório de OB - SIGEF'; require __DIR__ . '/partials/header.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #002244;">📊 Relatório de Ordens Bancárias (Liquidadas)</h2>
    <div>
        <button onclick="gerarDossieUnico()" id="btn-dossie" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-right: 10px;">🖨️ Gerar Dossiê Único (PDF)</button>
        <a href="/" style="background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar</a>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
    <form action="/relatorio/ob" method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <div><label>Data Inicial:</label><br><input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></div>
        <div><label>Data Final:</label><br><input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></div>
        <button type="submit" style="background: #6f42c1; color: white; border: none; padding: 9px 20px; border-radius: 4px; font-weight: bold;">Filtrar</button>
    </form>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <?php if(empty($relatorio)): ?>
        <p style="text-align: center; color: #666; padding: 30px 0;">Nenhum pagamento liquidado neste período.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding: 10px;">Data PGTO</th><th style="padding: 10px;">OB</th><th style="padding: 10px;">Fornecedor</th><th style="padding: 10px;">Arquivo(s)</th>
                </tr>
                <?php foreach($relatorio as $r): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= date('d/m/Y', strtotime($r['data_pagamento'])) ?></td>
                    <td style="padding: 10px; color: #6f42c1;"><b><?= htmlspecialchars($r['ob_numero']) ?></b></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($r['cpf_cnpj']) ?></td>
                    <td style="padding: 10px;">
                        <?php if($r['ob_arquivo']): ?>
                            <?php $paths = explode('|', $r['ob_arquivo']); foreach($paths as $idx => $p): ?>
                                <a href="<?= htmlspecialchars($p) ?>" class="link-pdf-ob" target="_blank" style="color: #004488; font-weight: bold; background: #e9ecef; padding: 5px 10px; border-radius: 4px; text-decoration: none; margin-bottom: 4px; display: inline-block;">
                                    📄 OB_<?= htmlspecialchars($r['ob_numero']) ?><?= count($paths) > 1 ? '_' . ($idx+1) : '' ?>.pdf
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:#999; font-size:0.9em;">Sem arquivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
async function gerarDossieUnico() {
    const btn = document.getElementById('btn-dossie');
    const links = document.querySelectorAll('.link-pdf-ob');
    if (links.length === 0) { alert('Não há PDFs neste período para unificar.'); return; }
    
    btn.innerText = "⏳ Gerando Dossiê (Isso pode levar alguns segundos)...";
    btn.disabled = true;

    try {
        const { PDFDocument } = PDFLib;
        const mergedPdf = await PDFDocument.create();

        for(let i = 0; i < links.length; i++) {
            const url = links[i].href;
            try {
                const existingPdfBytes = await fetch(url).then(res => res.arrayBuffer());
                const pdfDoc = await PDFDocument.load(existingPdfBytes);
                const copiedPages = await mergedPdf.copyPages(pdfDoc, pdfDoc.getPageIndices());
                copiedPages.forEach((page) => mergedPdf.addPage(page));
            } catch (err) {
                console.warn("Falha ao incluir o PDF:", url, err);
            }
        }

        const pdfBytes = await mergedPdf.save();
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Dossie_OBs_${new Date().getTime()}.pdf`;
        link.click();
        
        btn.innerText = "🖨️ Gerar Dossiê Único (PDF)";
    } catch (e) {
        alert('Erro fatal ao fundir PDFs. Verifique o console.');
        console.error(e);
        btn.innerText = "🖨️ Gerar Dossiê Único (PDF)";
    }
    btn.disabled = false;
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>