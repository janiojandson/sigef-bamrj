<?php $page_title = 'Aprovação - SIGEF'; require __DIR__ . '/partials/header.php'; 

// Organiza os itens agrupando pelo número do RAP
$itens_por_rap = [];
$itens_isolados = [];
foreach ($itens as $it) {
    if (!empty($it['numero_rap'])) {
        $itens_por_rap[$it['numero_rap']][] = $it;
    } else {
        $itens_isolados[] = $it;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">✍️ Fila de Assinaturas (Foco em RAPs)</h2>
        <p style="margin: 5px 0 0 0; color: #666;">Verifique e assine as chancelas financeiras agrupadas.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <?php if (in_array($_SESSION['role'], ['Gestor_Financeiro', 'Gestor_Substituto', 'Chefe_Departamento', 'Agente_Fiscal'])): ?>
            <form action="/assinador/toggleSubstituto" method="POST" style="margin:0;">
                <?php if($_SESSION['atuando_substituto'] ?? false): ?>
                    <button type="submit" class="btn btn-warning" style="font-weight:bold; box-shadow: 0 0 8px rgba(255, 193, 7, 0.8);">⚠️ Modo Substituto ATIVADO</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-outline-secondary">Habilitar Modo Substituto</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
        <a href="/" class="btn btn-secondary">⬅️ Dashboard</a>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #6f42c1;">
    <?php if (empty($itens)): ?>
        <p style="color: #28a745; font-weight: bold; text-align: center; padding: 30px; font-size: 1.2em;">✅ Fila Limpa. Não há documentos aguardando assinatura.</p>
    <?php else: ?>
        <form action="/assinador/acao" method="POST" id="form-assinatura">
            
            <div style="position: sticky; top: 0; z-index: 10; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; display: flex; gap: 10px; justify-content: flex-end; align-items: center; border: 1px solid #ccc; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <span style="font-weight: bold; color: #333; margin-right: auto;">Ações em Bloco (Itens Selecionados):</span>
                <input type="text" name="observacao" placeholder="Motivo (Obrigatório p/ Rejeitar)" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 300px;">
                <button type="submit" name="acao" value="aprovar" class="btn btn-success" style="padding: 10px 20px; font-weight: bold;">✅ Aprovar Selecionados</button>
                <button type="submit" name="acao" value="rejeitar" class="btn btn-danger" onclick="return confirm('Deseja DEVOLVER os itens selecionados?')" style="padding: 10px 20px;">❌ Rejeitar Selecionados</button>
            </div>

            <?php foreach ($itens_por_rap as $rap_num => $itens_do_rap): ?>
                <div style="border: 2px solid #002244; border-radius: 8px; margin-bottom: 25px; overflow: hidden;">
                    <div style="background: #002244; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">Capa RAP: <?= htmlspecialchars($rap_num) ?></h3>
                        <label style="cursor: pointer; font-weight: bold; font-size: 0.9em;">
                            <input type="checkbox" onclick="toggleGroup(this, 'rap-<?= md5($rap_num) ?>')" style="transform: scale(1.3); margin-right: 8px;"> Marcar Bloco Inteiro
                        </label>
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="background: #f1f3f5; border-bottom: 2px solid #ddd; text-align: left;">
                            <th style="padding: 10px; width: 40px; text-align: center;">Sel.</th>
                            <th style="padding: 10px;">ID / Status</th>
                            <th style="padding: 10px;">OP / NP</th>
                            <th style="padding: 10px;">Documento / Fornecedor</th>
                        </tr>
                        <?php foreach ($itens_do_rap as $item): ?>
                            <?= renderAssinadorRow($item, "rap-" . md5($rap_num)) ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($itens_isolados)): ?>
                <div style="border: 2px solid #6c757d; border-radius: 8px; margin-bottom: 25px; overflow: hidden;">
                    <div style="background: #6c757d; color: white; padding: 10px 15px;">
                        <h3 style="margin: 0;">📄 Itens Isolados (Sem RAP / Avulsos)</h3>
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <?php foreach ($itens_isolados as $item): ?>
                            <?= renderAssinadorRow($item, "isolados") ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

        </form>
    <?php endif; ?>
</div>

<?php 
function renderAssinadorRow($item, $group_class) {
    $is_rejeitado = str_contains($item['observacao_atual'] ?? '', 'REJEITADO') || str_contains($item['observacao_atual'] ?? '', 'DEVOLVIDO');
    $html = "<tr style='border-bottom: 1px solid #eee; " . ($item['prioridade'] ? "background: #fff5f5;" : "") . "'>";
    
    $html .= "<td style='padding: 12px; text-align: center;'><input type='checkbox' name='itens_selecionados[]' value='{$item['id']}' class='item-checkbox {$group_class}' style='transform: scale(1.3); cursor: pointer;'></td>";
    
    $html .= "<td style='padding: 12px;'>
                <span style='background: #333; color: #fff; padding: 3px 6px; border-radius: 4px; font-family: monospace; font-weight: bold;'>#".str_pad($item['id'], 5, '0', STR_PAD_LEFT)."</span>";
    if ($is_rejeitado) $html .= "<br><span style='display:inline-block; margin-top:5px; background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.75em; font-weight: bold;'>🚨 DEVOLVIDO</span>";
    if ($item['prioridade']) $html .= "<br><span style='display:inline-block; margin-top:5px; color:#dc3545; font-weight:bold; font-size:0.85em;'>🚩 URGENTE</span>";
    $html .= "</td>";

    $html .= "<td style='padding: 12px;'>
                <b style='color: #6f42c1; font-size: 1.1em;'>OP: " . htmlspecialchars($item['op_numero'] ?? '---') . "</b><br>
                <small style='color: #004488;'>NP: " . htmlspecialchars($item['np_numero'] ?? '---') . "</small>
              </td>";

    $html .= "<td style='padding: 12px;'>
                NF: <b>" . htmlspecialchars($item['num_documento_fiscal']) . "</b><br>
                <small>CNPJ: " . htmlspecialchars($item['cpf_cnpj']) . "</small>
              </td>";
    
    $html .= "</tr>";
    return $html;
}
?>

<script>
function toggleGroup(source, className) {
    var checkboxes = document.getElementsByClassName(className);
    for(var i=0; i<checkboxes.length; i++) { checkboxes[i].checked = source.checked; }
}
document.getElementById('form-assinatura').addEventListener('submit', function(e) {
    var checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) { e.preventDefault(); alert('Selecione pelo menos um item/checkbox para processar.'); }
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
