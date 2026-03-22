<?php
$page_title = 'Nova DE - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php'; // Usa o cabeçalho existente do Assinador
?>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #004488; max-width: 800px; margin: 0 auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #002244;">📄 Lançamento de Documento de Encaminhamento (DE)</h2>
        <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar</a>
    </div>

    <div id="alerta-omap" style="display: none; background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border-left: 5px solid #ffeeba; margin-bottom: 20px; font-weight: bold;">
        ⚠️ ATENÇÃO OMAP: A PA deve estar na conta contábil 213110400. Verifique se a FAT/NF constam dados bancários explicitamente antes de prosseguir.
    </div>

    <form action="/de/store" method="POST">
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #333;">1. Origem do Lote</h4>
            <label style="font-weight: bold; color: #555;">Setor Remetente:</label>
            <select name="origem" id="select-origem" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em;">
                <option value="">-- Selecione a Origem --</option>
                <option value="OMAP">OMAP (Pagamento via PA)</option>
                <option value="BAMRJ">Setor Interno BAMRJ (Outros)</option>
            </select>
        </div>

        <div style="background: #ffffff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #333;">2. Dados do Item (Nota/Fatura)</h4>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: bold; color: #555;">CPF/CNPJ do Favorecido:</label>
                    <input type="text" name="cpf_cnpj" required placeholder="Apenas números" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: bold; color: #555;">Nº do Documento (NF/FAT/OS):</label>
                    <input type="text" name="num_doc_fiscal" required placeholder="Ex: NF 12345" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-weight: bold; color: #555;">Valor Total (R$):</label>
                    <input type="text" name="valor_total" required placeholder="Ex: 1500,00" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <div id="box-pa" style="flex: 1; min-width: 200px; display: none;">
                    <label style="font-weight: bold; color: #d32f2f;">Número da NS da PA (Obrigatório):</label>
                    <input type="text" name="pa_numero" id="input-pa" placeholder="Ex: 2026NS000123" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #d32f2f; border-radius: 4px; background: #fff5f5;">
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: bold; color: #555;">Observação / Despacho Inicial:</label>
            <textarea name="observacao" required placeholder="Descreva brevemente o motivo do pagamento..." style="width: 100%; height: 80px; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
        </div>

        <button type="submit" style="width: 100%; background: #28a745; color: white; padding: 15px; border: none; font-size: 1.1em; font-weight: bold; border-radius: 4px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            ✅ GERAR DE E ENVIAR AO PROTOCOLO
        </button>
    </form>
</div>

<script>
document.getElementById('select-origem').addEventListener('change', function() {
    var origem = this.value;
    var alertaOmap = document.getElementById('alerta-omap');
    var boxPa = document.getElementById('box-pa');
    var inputPa = document.getElementById('input-pa');

    if (origem === 'OMAP') {
        alertaOmap.style.display = 'block';
        boxPa.style.display = 'block';
        inputPa.setAttribute('required', 'required'); // Torna PA obrigatória
    } else {
        alertaOmap.style.display = 'none';
        boxPa.style.display = 'none';
        inputPa.removeAttribute('required'); // Remove a obrigatoriedade
        inputPa.value = ''; // Limpa o campo se ocultado
    }
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>