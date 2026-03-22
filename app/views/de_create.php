<?php
$page_title = 'Nova DE - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';

$origem = $_SESSION['origem_setor'] ?? 'BAMRJ';
// 🛡️ Identifica inteligentemente se é OMAP
$is_omap = str_starts_with($origem, 'OMAP');
?>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #004488; max-width: 900px; margin: 0 auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #002244;">📄 Lançamento de Lote (DE)</h2>
        <a href="/" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Voltar</a>
    </div>

    <form action="/de/store" method="POST" id="form-de">

        <?php if ($is_omap): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border-left: 5px solid #ffeeba; margin-bottom: 20px;">
                <p style="margin-top: 0; font-weight: bold; font-size: 1.05em;">⚠️ ATENÇÃO OMAP: A PA deve estar na conta contábil 213110400. Verifique se a FAT/NF constam dados bancários explicitamente antes de prosseguir.</p>
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: #ffeeba; padding: 10px; border-radius: 4px; border: 1px solid #e2c074;">
                    <input type="checkbox" name="ciente_regras" required style="transform: scale(1.3); cursor: pointer;">
                    <b style="color: #664d03;">Li o aviso acima, conferi os dados e declaro que estão corretos.</b>
                </label>
            </div>
        <?php else: ?>
            <div style="background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; border-left: 5px solid #b8daff; margin-bottom: 20px;">
                <p style="margin-top: 0; font-weight: bold; font-size: 1.05em;">⚠️ ATENÇÃO BAMRJ: Verifique se a FAT/NF constam dados bancários explicitamente antes de prosseguir.</p>
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: #b8daff; padding: 10px; border-radius: 4px; border: 1px solid #8cb8e6;">
                    <input type="checkbox" name="ciente_regras" required style="transform: scale(1.3); cursor: pointer;">
                    <b style="color: #002752;">Li o aviso acima, conferi os dados e declaro que estão corretos.</b>
                </label>
            </div>
        <?php endif; ?>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 20px;">
            <label style="font-weight: bold; color: #555;">Origem da DE (Automático pelo seu Perfil):</label>
            <input type="text" value="<?= htmlspecialchars($origem) ?>" readonly style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; background: #e9ecef; font-weight: bold; color: #333;">
        </div>

        <h4 style="color: #333;">📦 Itens do Lote (Notas/Faturas)</h4>
        
        <div id="itens-container">
            <div class="item-row" style="background: #ffffff; padding: 15px; border-radius: 5px; border: 1px dashed #004488; margin-bottom: 15px; position: relative;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 10px; align-items: flex-end;">
                    <div style="flex: 1; min-width: 150px;">
                        <label style="font-size: 0.9em; font-weight: bold;">CPF/CNPJ:</label>
                        <input type="text" name="cpf_cnpj[]" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label style="font-size: 0.9em; font-weight: bold;">Nº Documento:</label>
                        <input type="text" name="num_doc_fiscal[]" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="flex: 1; min-width: 100px;">
                        <label style="font-size: 0.9em; font-weight: bold;">Valor (R$):</label>
                        <input type="text" name="valor_total[]" required placeholder="0,00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <?php if ($is_omap): ?>
                    <div style="flex: 1; min-width: 150px;">
                        <label style="font-size: 0.9em; font-weight: bold; color: #d32f2f;">NS da PA:</label>
                        <input type="text" name="pa_numero[]" required style="width: 100%; padding: 8px; border: 1px solid #d32f2f; border-radius: 4px; background: #fff5f5;">
                    </div>
                    <?php endif; ?>
                    
                    <div style="flex: 1; min-width: 150px; background: #fff5f5; padding: 8px; border-radius: 4px; border: 1px solid #ffcccc; display: flex; align-items: center; gap: 8px;">
                        <input type="hidden" name="prioridade_flag[]" value="0">
                        <input type="checkbox" onchange="this.previousElementSibling.value = this.checked ? '1' : '0'" style="transform: scale(1.3); cursor: pointer;">
                        <label style="font-size: 0.9em; font-weight: bold; color: #dc3545; cursor: pointer;">🚩 Marcar Prioridade</label>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" onclick="adicionarItem()" style="background: #004488; color: white; padding: 10px 15px; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; margin-bottom: 20px;">
            ➕ ADICIONAR OUTRO ITEM AO LOTE
        </button>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: bold; color: #555;">Observação Geral (Obrigatório):</label>
            <textarea name="observacao" required placeholder="Descreva o motivo deste encaminhamento..." style="width: 100%; height: 80px; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
        </div>

        <button type="submit" style="width: 100%; background: #28a745; color: white; padding: 15px; border: none; font-size: 1.1em; font-weight: bold; border-radius: 4px; cursor: pointer;">
            🚀 ENCAMINHAR LOTE COMPLETO
        </button>
    </form>
</div>

<script>
function adicionarItem() {
    var container = document.getElementById('itens-container');
    var primeiroItem = container.querySelector('.item-row');
    var novoItem = primeiroItem.cloneNode(true);
    
    // Limpa os valores de texto
    var inputsText = novoItem.querySelectorAll('input[type="text"]');
    inputsText.forEach(input => input.value = '');
    
    // Reseta a flag de prioridade
    var hiddenFlag = novoItem.querySelector('input[type="hidden"]');
    if (hiddenFlag) hiddenFlag.value = '0';
    
    var checkboxes = novoItem.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(chk => chk.checked = false);
    
    // Adiciona botão de remover
    var btnRemover = document.createElement('button');
    btnRemover.innerHTML = "❌ Remover Item";
    btnRemover.style = "position: absolute; top: -10px; right: 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; padding: 3px 8px; font-size: 0.8em;";
    btnRemover.onclick = function() { novoItem.remove(); };
    
    novoItem.appendChild(btnRemover);
    container.appendChild(novoItem);
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>