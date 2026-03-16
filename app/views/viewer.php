<?php
$page_title = 'Visualizador de Processo';
$hide_navbar = true; 
require __DIR__ . '/partials/header.php';

$docCtrl = new \App\Controllers\DocumentController();
$dados = $docCtrl->getViewerData();

$doc = $dados['doc'];
$files = $dados['files'];
$role = $dados['role'];
?>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<style>
    .container { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .viewer-container { display: flex; height: 100vh; overflow: hidden; background: #e9ecef; }
    
    .pdf-area-left { flex: 1; background: #525659; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; }
    .sidebar-right { width: 420px; background: white; padding: 25px; overflow-y: auto; border-left: 3px solid #002244; display: flex; flex-direction: column; box-shadow: -4px 0 10px rgba(0,0,0,0.1); }
    
    .btn-group { display: flex; gap: 10px; margin-top: 15px; }
    .btn-group button { flex: 1; padding: 12px; font-size: 1.05em; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; color: white; transition: 0.2s; }
    .btn-aprovar { background-color: #28a745; }
    .btn-aprovar:hover { background-color: #218838; }
    .btn-devolver { background-color: #dc3545; }
    .btn-devolver:hover { background-color: #c82333; }
</style>

<div class="viewer-container">
    
    <div class="pdf-area-left">
        <?php if (count($files) > 0): ?>
            <h3 id="loading-msg" style="color: white; text-align: center; margin-top: 20px;">⚙️ A unificar e decodificar documentos... Aguarde.</h3>
            
            <iframe id="single-pdf-viewer" style="width: 100%; height: 95vh; max-width: 1200px; border: none; display: none; background: white; border-radius: 5px; box-shadow: 0 10px 20px rgba(0,0,0,0.4);" src=""></iframe>
            
            <script>
                document.addEventListener("DOMContentLoaded", async () => {
                    const pdfUrls = [
                        <?php foreach ($files as $f): ?>
                            "/get_pdf?file=<?= urlencode($f['filename']) ?>",
                        <?php endforeach; ?>
                    ];
                    
                    try {
                        const { PDFDocument } = PDFLib;
                        const mergedPdf = await PDFDocument.create();
                        
                        for (let url of pdfUrls) {
                            const pdfBytes = await fetch(url).then(res => res.arrayBuffer());
                            const pdf = await PDFDocument.load(pdfBytes);
                            const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices());
                            copiedPages.forEach((page) => mergedPdf.addPage(page));
                        }
                        
                        const mergedPdfFile = await mergedPdf.save();
                        const blob = new Blob([mergedPdfFile], { type: 'application/pdf' });
                        const blobUrl = URL.createObjectURL(blob);
                        
                        const viewer = document.getElementById('single-pdf-viewer');
                        viewer.src = blobUrl + "#toolbar=1&navpanes=0";
                        viewer.style.display = 'block';
                        document.getElementById('loading-msg').style.display = 'none';
                    } catch (err) {
                        console.error("Erro Tático na Fusão de PDFs: ", err);
                        document.getElementById('loading-msg').innerText = "⚠️ Ocorreu um erro ao unificar os documentos. Tente recarregar a página.";
                    }
                });
            </script>
        <?php else: ?>
            <div style="color: white; padding: 20px; text-align: center; font-size: 1.2em; margin-top: 50px;">
                ⚠️ Nenhum documento PDF liberado ou anexado a este processo.
            </div>
        <?php endif; ?>
    </div>

    <div class="sidebar-right">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="<?= $role === 'Usuário Comum' ? '/arquivo' : '/' ?>" style="color: white; background: #6c757d; text-decoration: none; font-weight: bold; padding: 8px 15px; border-radius: 4px;">⬅️ Voltar</a>
            
            <?php if ($role === 'Operador' && in_array($doc['status'], ['Devolvido - Operador', 'Arquivado', 'Cancelado', 'Anulado', 'Reforçado'])): ?>
                <a href="/edit?id=<?= $doc['id'] ?>" style="background: #ffcc00; color: #002244; text-decoration: none; font-weight: bold; padding: 8px 15px; border-radius: 4px;">✏️ Editar/Reiniciar</a>
            <?php endif; ?>
        </div>
        
        <h3 style="margin-top:0; color: #002244; border-bottom: 2px solid #eee; padding-bottom: 10px;">Protocolo:<br><span style="color: #d32f2f; font-family: monospace; font-size: 1.2em;"><?= htmlspecialchars($doc['protocol']) ?></span></h3>
        
        <p style="margin: 5px 0;"><b>Assunto:</b><br> <?= htmlspecialchars($doc['name']) ?></p>
        <p style="margin: 5px 0;"><b>CPF/CNPJ:</b> <?= htmlspecialchars($doc['cpf_cnpj']) ?: '-' ?></p>
        <p style="margin: 5px 0;"><b>SOLEMP:</b> <?= htmlspecialchars($doc['solemp']) ?: '-' ?></p>
        <p style="margin: 5px 0;"><b>Status Atual:</b><br> <span style="background: #004488; color: white; padding: 4px 8px; border-radius: 3px; font-weight:bold; display: inline-block; margin-top: 5px;"><?= htmlspecialchars($doc['status']) ?></span></p>
        
        <hr style="border-top: 1px solid #ddd; margin: 20px 0; width: 100%;">

        <div style="background: #f1f3f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
            <h4 style="margin-top: 0; color: #333; margin-bottom: 15px;"><span style="font-size: 1.2em;">📎</span> Documentos do Processo (<?= count($files) ?>):</h4>
            
            <?php foreach ($files as $f): 
                $tipo = $f['file_type'];
                $nome = basename($f['filename']);
                // Sistema de Cores Táticas
                if ($tipo === 'Nota de Empenho') { $corBorda = '#28a745'; $bg = '#d4edda'; $corBotao = '#28a745'; }
                elseif ($tipo === 'Minuta') { $corBorda = '#17a2b8'; $bg = '#ffffff'; $corBotao = '#17a2b8'; }
                else { $corBorda = '#6c757d'; $bg = '#ffffff'; $corBotao = '#6c757d'; }
            ?>
                <div style="display: flex; align-items: center; background: <?= $bg ?>; border: 1px solid #ddd; border-left: 4px solid <?= $corBorda ?>; border-radius: 4px; padding: 10px; margin-bottom: 10px; transition: 0.2s;">
                    <a href="/get_pdf?file=<?= urlencode($f['filename']) ?>&dl=1" style="background: <?= $corBotao ?>; color: white; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 4px; text-decoration: none; font-size: 1.2em; margin-right: 12px; flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Baixar <?= $tipo ?>">⬇️</a>
                    <div style="overflow: hidden;">
                        <strong style="color: <?= $corBorda ?>;">[<?= $tipo ?>]</strong><br>
                        <span style="font-size: 0.85em; color: #555; word-wrap: break-word;"><?= htmlspecialchars($nome) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($files)): ?>
                <p style="color: #666; font-size: 0.9em; text-align: center;">Nenhum documento anexado.</p>
            <?php endif; ?>
        </div>

        <?php if (in_array($role, ['Enc_Financas', 'Ajudante_Encarregado', 'Chefe_Departamento', 'Vice_Diretor', 'Diretor']) && strpos($doc['status'], 'Caixa de Entrada') !== false): ?>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 2px solid #002244; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h4 style="margin-top: 0; color: #002244; margin-bottom: 10px; text-align: center;">✍️ Despacho Tático</h4>
                <form action="/process_action?id=<?= $doc['id'] ?>" method="POST" id="form-despacho">
                    <textarea name="new_observation" id="obs" required placeholder="Escreva aqui o seu despacho oficial (Campo Obrigatório)..." style="width: 100%; height: 120px; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; resize: vertical; font-family: inherit; font-size: 1em;"></textarea>
                    <div class="btn-group">
                        <button type="submit" name="action" value="aprovar" class="btn-aprovar" onclick="return confirm('Confirmar APROVAÇÃO do processo?');">✅ Aprovar e Tramitar</button>
                        <button type="submit" name="action" value="rejeitar" class="btn-devolver" onclick="return confirm('ATENÇÃO: O processo será REJEITADO e devolvido ao Operador. Deseja prosseguir?');">❌ Rejeitar e Devolver</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($role !== 'Usuário Comum'): ?>
            <h4 style="color: #002244; margin-bottom: 10px;">📜 Histórico da Tramitação</h4>
            <div style="flex: 1; font-size: 0.85em; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-y: auto; white-space: pre-wrap; font-family: monospace; line-height: 1.5; color: #333; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                <?= htmlspecialchars($doc['current_observation']) ?>
            </div>
        <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; text-align: center; margin-top: 20px; border: 1px solid #ffeeba;">
                🔒 <b>Acesso Restrito:</b> Histórico confidencial.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>