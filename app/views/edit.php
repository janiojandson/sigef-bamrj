<?php
$page_title = 'Editar/Reiniciar Processo - Assinador BAMRJ';
require __DIR__ . '/partials/header.php';
?>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 5px solid #ffcc00; margin-bottom: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h2 style="margin: 0; color: #002244;">✏️ Edição e Reinício de Demanda</h2>
        <a href="/" style="background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Cancelar e Voltar</a>
    </div>

    <h4 style="color: #002244; margin-bottom: 10px;">📜 Histórico / Motivo (Para sua referência)</h4>
    <div style="font-size: 0.9em; background: #fff3cd; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; overflow-y: auto; max-height: 150px; white-space: pre-wrap; font-family: monospace; line-height: 1.5; color: #856404; margin-bottom: 25px;">
        <?= htmlspecialchars($doc['current_observation']) ?>
    </div>

    <form action="/edit?id=<?= $doc['id'] ?>" method="POST" enctype="multipart/form-data">
        
        <h4 style="color: #002244; margin-bottom: 10px;">📝 Modificar Dados do Processo</h4>
        <p style="margin-bottom: 5px;"><strong>Protocolo:</strong> <code style="color: #d32f2f; font-size: 1.1em;"><?= htmlspecialchars($doc['protocol']) ?></code></p>
        
        <label style="font-weight: bold; font-size: 0.9em; color: #555;">Assunto:</label>
        <input type="text" name="process_name" value="<?= htmlspecialchars($doc['name']) ?>" required style="width: 100%; padding: 10px; margin-bottom: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
        
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <div style="flex: 1;">
                <label style="font-weight: bold; font-size: 0.9em; color: #555;">CPF/CNPJ:</label>
                <input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($doc['cpf_cnpj']) ?>" style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1;">
                <label style="font-weight: bold; font-size: 0.9em; color: #555;">Nº SOLEMP:</label>
                <input type="text" name="solemp" value="<?= htmlspecialchars($doc['solemp']) ?>" style="width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <hr style="border-top: 1px solid #eee; margin: 25px 0;">

        <h4 style="color: #002244;">📎 Adicionar Novos Documentos Corrigidos (Opcional)</h4>
        <p style="font-size: 0.85em; color: #666; margin-top: -10px;">Os PDFs atuais não serão apagados. Anexe apenas os novos arquivos corrigidos, se houver.</p>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px; background: #e9ecef; padding: 15px; border-radius: 5px; border: 1px dashed #ccc;">
                <label><b>Novas Minutas (PDF):</b></label><br>
                <input type="file" id="m-in" accept="application/pdf" multiple style="margin-top:10px; width: 100%;">
                <ul id="m-list" style="font-size: 0.85em; color: #666; padding-left: 20px; margin-top: 10px;"></ul>
                <input type="file" name="minutas[]" id="m-hidden" multiple style="display: none;">
            </div>
            <div style="flex: 1; min-width: 250px; background: #e9ecef; padding: 15px; border-radius: 5px; border: 1px dashed #ccc;">
                <label><b>Novos Anexos (PDF):</b></label><br>
                <input type="file" id="a-in" accept="application/pdf" multiple style="margin-top:10px; width: 100%;">
                <ul id="a-list" style="font-size: 0.85em; color: #666; padding-left: 20px; margin-top: 10px;"></ul>
                <input type="file" name="anexos[]" id="a-hidden" multiple style="display: none;">
            </div>
        </div>

        <h4 style="color: #002244;">✍️ Despacho de Reinício (Obrigatório)</h4>
        <textarea name="observation" required placeholder="Informe as correções realizadas ou por que está reiniciando este processo..." style="width: 100%; height: 100px; margin-bottom: 20px; padding: 12px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 1em;"></textarea>
        
        <button type="submit" style="width: 100%; background: #28a745; color: white; padding: 15px; border: none; cursor: pointer; font-weight: bold; font-size: 1.1em; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            🔄 Reenviar e Reiniciar Tramitação
        </button>
    </form>
</div>

<script>
const dtM = new DataTransfer(), dtA = new DataTransfer();
function setupFiles(inId, hidId, listId, dt) {
    const inp = document.getElementById(inId), hid = document.getElementById(hidId), list = document.getElementById(listId);
    if(!inp) return;
    inp.addEventListener('change', () => { for(let f of inp.files) dt.items.add(f); renderFiles(list, hid, dt); });
}
function renderFiles(list, hid, dt) {
    list.innerHTML = ''; hid.files = dt.files;
    Array.from(dt.files).forEach((f, i) => {
        const li = document.createElement('li');
        li.innerHTML = f.name + ' <b style="cursor:pointer;color:#dc3545;margin-left:10px">[X]</b>';
        li.querySelector('b').onclick = () => { dt.items.remove(i); renderFiles(list, hid, dt); };
        list.appendChild(li);
    });
}
setupFiles('m-in', 'm-hidden', 'm-list', dtM);
setupFiles('a-in', 'a-hidden', 'a-list', dtA);
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>