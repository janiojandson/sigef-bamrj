<?php
$page_title = 'Painel do Operador - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';

use App\core\Database;
$db = Database::getConnection();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #dc3545;">
    <div>
        <h2 style="margin: 0; color: #002244;">Execução Financeira (Operador)</h2>
        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Fila de Análise e Pagamentos</p>
    </div>
</div>

<?php if(isset($_GET['alerta']) && $_GET['alerta'] === 'veto_aplicado'): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-weight: bold;">
        🛑 VETO APLICADO COM SUCESSO! O item foi devolvido para a origem.
    </div>
<?php endif; ?>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">Caixa de Entrada (Lotes Ativos)</h3>
    
    <?php if(empty($des)): ?>
        <p style="color: #666;">Nenhum lote pendente de análise.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 12px;">Origem / Criador</th>
                    <th style="padding: 12px;">ID Lote</th>
                    <th style="padding: 12px;">Status Geral do Lote</th>
                    <th style="padding: 12px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($des as $de): 
                    $stmt = $db->prepare("SELECT * FROM itens_de WHERE de_id = ? ORDER BY id ASC");
                    $stmt->execute([$de['id']]);
                    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">
                        <b><?= htmlspecialchars($de['unit_omap'] ?: 'SETOR INTERNO') ?></b><br>
                        <small style="color: #666;"><?= htmlspecialchars($de['criador_nome']) ?></small>
                    </td>
                    <td style="padding: 12px;"><code style="font-size: 1.1em; color: #004488;">#<?= str_pad($de['id'], 5, '0', STR_PAD_LEFT) ?></code></td>
                    <td style="padding: 12px;"><span style="background: #f39c12; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold;"><?= htmlspecialchars($de['status_geral']) ?></span></td>
                    <td style="padding: 12px; text-align: center;">
                        <button onclick="toggleItens(<?= $de['id'] ?>)" style="background: #004488; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            Expandir Lote ⬇️
                        </button>
                    </td>
                </tr>
                
                <tr id="itens_<?= $de['id'] ?>" style="display: none; background: #fff5f5;">
                    <td colspan="4" style="padding: 15px; border-bottom: 3px solid #dc3545;">
                        <div style="border-left: 4px solid #dc3545; padding-left: 15px;">
                            <h4 style="margin: 0 0 10px 0; color: #002244;">Análise Granular (Itens do Lote):</h4>
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
                                <?php foreach($itens as $item): ?>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 10px; width: 40%;">📄 <b><?= htmlspecialchars($item['nome_documento']) ?></b></td>
                                    <td style="padding: 10px; width: 20%;">
                                        <span style="background: #e2e3e5; padding: 4px 8px; border-radius: 4px;"><?= htmlspecialchars($item['status_item']) ?></span>
                                    </td>
                                    <td style="padding: 10px; text-align: right;">
                                        <?php if($item['status_item'] !== 'DEVOLVIDO_OMAP'): ?>
                                            <button onclick="abrirVeto(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nome_documento'], ENT_QUOTES) ?>')" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.85em;">
                                                🛑 Aplicar Veto (Rejeitar)
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #dc3545; font-weight: bold;">⚠️ Aguardando correção pela origem</span>
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

<div id="modalVeto" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000;">
    <div style="background: white; width: 400px; max-width: 90%; margin: 100px auto; padding: 25px; border-radius: 8px; border-top: 5px solid #dc3545; text-align: center;">
        <h2 style="color: #dc3545; margin-top: 0;">🛑 APLICAR VETO</h2>
        <p style="color: #333; margin-bottom: 20px;">Você está rejeitando o documento:<br><b id="vetoNomeDoc" style="font-size: 1.1em; color: #002244;"></b></p>
        
        <form action="/operador/veto" method="POST" style="text-align: left;">
            <input type="hidden" name="item_id" id="vetoItemId">
            
            <label style="font-weight: bold; color: #002244; display: block; margin-bottom: 5px;">Despacho / Motivo da Rejeição:</label>
            <textarea name="motivo_rejeicao" required placeholder="Explique o erro para que a OMAP possa corrigir..." style="width: 100%; padding: 10px; height: 100px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; box-sizing: border-box;"></textarea>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="fecharVeto()" style="background: #e2e3e5; color: #333; border: none; padding: 10px; border-radius: 4px; cursor: pointer; flex: 1; font-weight: bold;">Cancelar</button>
                <button type="submit" style="background: #dc3545; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; flex: 1; font-weight: bold;">Confirmar Veto</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleItens(id) {
    var el = document.getElementById('itens_' + id);
    el.style.display = (el.style.display === 'none') ? 'table-row' : 'none';
}

function abrirVeto(itemId, docName) {
    document.getElementById('vetoItemId').value = itemId;
    document.getElementById('vetoNomeDoc').innerText = docName;
    document.getElementById('modalVeto').style.display = 'block';
}

function fecharVeto() {
    document.getElementById('modalVeto').style.display = 'none';
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>