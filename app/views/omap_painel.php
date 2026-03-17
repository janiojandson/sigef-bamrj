<?php
$page_title = 'Painel de Origem - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';

use App\core\Database;
$db = Database::getConnection();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #004488;">
    <div>
        <h2 style="margin: 0; color: #002244;">Painel de Remessa (<?= htmlspecialchars($_SESSION['role']) ?>)</h2>
        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Criação de Lotes e Rastreio de Itens</p>
    </div>
    <button onclick="document.getElementById('modalDE').style.display='block'" style="background: #28a745; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 1em;">
        ➕ Novo Lote de Documentos
    </button>
</div>

<?php if(isset($_GET['sucesso'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✅ Lote enviado com sucesso para a Execução Financeira!
    </div>
<?php endif; ?>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">Lotes Enviados</h3>
    
    <?php if(empty($des)): ?>
        <p style="color: #666;">Nenhum lote enviado ainda.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 12px;">ID Lote</th>
                    <th style="padding: 12px;">Status Geral</th>
                    <th style="padding: 12px;">Data de Envio</th>
                    <th style="padding: 12px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($des as $de): 
                    // Busca os Itens deste Lote
                    $stmt = $db->prepare("SELECT * FROM itens_de WHERE de_id = ? ORDER BY id ASC");
                    $stmt->execute([$de['id']]);
                    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><b>#<?= str_pad($de['id'], 5, '0', STR_PAD_LEFT) ?></b></td>
                    <td style="padding: 12px;">
                        <span style="background: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold;">
                            <?= htmlspecialchars($de['status_geral']) ?>
                        </span>
                    </td>
                    <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($de['criado_em'])) ?></td>
                    <td style="padding: 12px; text-align: center;">
                        <button onclick="toggleItens(<?= $de['id'] ?>)" style="background: #004488; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9em;">
                            Ver Documentos ⬇️
                        </button>
                    </td>
                </tr>
                <tr id="itens_<?= $de['id'] ?>" style="display: none; background: #fdfdfd;">
                    <td colspan="4" style="padding: 15px; border-bottom: 2px solid #004488;">
                        <div style="border-left: 3px solid #004488; padding-left: 15px;">
                            <h4 style="margin: 0 0 10px 0; color: #333;">Documentos deste Lote:</h4>
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
                                <?php foreach($itens as $item): ?>
                                <tr style="border-bottom: 1px dashed #ccc;">
                                    <td style="padding: 8px;">📄 <?= htmlspecialchars($item['nome_documento']) ?></td>
                                    <td style="padding: 8px;">
                                        <?php if($item['status_item'] === 'DEVOLVIDO_OMAP'): ?>
                                            <span style="color: #dc3545; font-weight: bold;">🔴 REJEITADO (Veto)</span>
                                            <div style="font-size: 0.85em; color: #dc3545; margin-top: 4px;">Motivo: <?= htmlspecialchars($item['motivo_rejeicao']) ?></div>
                                        <?php else: ?>
                                            <span style="color: #28a745; font-weight: bold;">🟢 <?= htmlspecialchars($item['status_item']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="modalDE" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; width: 500px; max-width: 90%; margin: 50px auto; padding: 25px; border-radius: 8px; border-top: 5px solid #002244;">
        <h3 style="margin-top: 0;">Novo Documento de Encaminhamento (Lote)</h3>
        <form action="/omap/criar_de" method="POST">
            
            <?php if($_SESSION['role'] === 'OMAP'): ?>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Número da NS (Pedido de Aquisição):</label>
                <input type="text" name="ns_pa" required placeholder="Ex: 2024NS000123" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            <?php endif; ?>

            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Documentos (Notas Fiscais / Recibos):</label>
            <div id="docs_container">
                <input type="text" name="nomes_documentos[]" required placeholder="Ex: Nota Fiscal nº 5530" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <button type="button" onclick="addDoc()" style="background: #e2e3e5; color: #333; border: 1px solid #ccc; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-bottom: 20px; font-size: 0.9em;">
                + Adicionar mais um documento
            </button>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('modalDE').style.display='none'" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Cancelar</button>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer;">Enviar Lote</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleItens(id) {
    var el = document.getElementById('itens_' + id);
    el.style.display = (el.style.display === 'none') ? 'table-row' : 'none';
}

function addDoc() {
    var container = document.getElementById('docs_container');
    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'nomes_documentos[]';
    input.placeholder = 'Ex: Recibo de Serviço';
    input.style.cssText = 'width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;';
    container.appendChild(input);
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>