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
        <h3 style="color: #28a745; margin-top: 0;">Total Liquidado: R$ <?= number_format($valor_total_periodo, 2, ',', '.') ?></h3>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                    <th style="padding: 10px;">Data PGTO</th><th style="padding: 10px;">OB</th><th style="padding: 10px;">Fornecedor</th><th style="padding: 10px;">Arquivo Original</th>
                </tr>
                <?php foreach($relatorio as $r): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= date('d/m/Y', strtotime($r['data_pagamento'])) ?></td>
                    <td style="padding: 10px; color: #6f42c1;"><b><?= htmlspecialchars($r['ob_numero']) ?></b></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($r['cpf_cnpj']) ?></td>
                    <td style="padding: 10px;">
                        <?php if($r['ob_arquivo']): ?>
                            <a href="<?= $r['ob_arquivo'] ?>" class="link-pdf-ob" target="_blank" style="color: #004488; font-weight: bold;">📄 Ver PDF</a>
                        <?php else: ?>
                            Sem arquivo
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
    
    btn.innerText = "⏳ Gerando Dossiê (Aguarde)...";
    btn.disabled = true;

    try {
        const { PDFDocument } = PDFLib;
        const mergedPdf = await PDFDocument.create();

        for(let i = 0; i < links.length; i++) {
            const url = links[i].href;
            const existingPdfBytes = await fetch(url).then(res => res.arrayBuffer());
            const pdfDoc = await PDFDocument.load(existingPdfBytes);
            const copiedPages = await mergedPdf.copyPages(pdfDoc, pdfDoc.getPageIndices());
            copiedPages.forEach((page) => mergedPdf.addPage(page));
        }

        const pdfBytes = await mergedPdf.save();
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Dossie_OBs_${new Date().getTime()}.pdf`;
        link.click();
        
        btn.innerText = "🖨️ Gerar Dossiê Único (PDF)";
    } catch (e) {
        alert('Erro ao fundir PDFs. Verifique se os arquivos existem.');
        console.error(e);
        btn.innerText = "🖨️ Gerar Dossiê Único (PDF)";
    }
    btn.disabled = false;
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>