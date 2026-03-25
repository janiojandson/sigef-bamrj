<?php $page_title = 'Aprovação de Pagamento - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #002244;">✍️ Fila de Assinaturas Pendentes</h2>
        <p style="margin: 5px 0 0 0; color: #666;">Documentos prontos para sua avaliação e chancela.</p>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <?php if (in_array($_SESSION['role'], ['Gestor_Financeiro', 'Gestor_Substituto', 'Chefe_Departamento', 'Agente_Fiscal'])): ?>
            <form action="/assinador/toggleSubstituto" method="POST" style="margin:0;">
                <?php if($_SESSION['atuando_substituto'] ?? false): ?>
                    <button type="submit" class="btn btn-warning" style="font-weight:bold; box-shadow: 0 0 8px rgba(255, 193, 7, 0.8);">⚠️ Modo Substituto ATIVADO (Desativar)</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-outline-secondary" title="Assumir caneta da instância superior">Habilitar Modo Substituto</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
        
        <a href="/" class="btn btn-secondary">⬅️ Dashboard</a>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #6f42c1;">
    
    <?php if (empty($itens)): ?>
        <p style="color: #28a745; font-weight: bold; text-align: center; padding: 30px; font-size: 1.2em;">✅ Fila Limpa. Não há documentos aguardando sua assinatura no momento.</p>
    <?php else: ?>
        <form action="/assinador/acao" method="POST" id="form-assinatura">
            
            <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px; display: flex; gap: 10px; justify-content: flex-end; align-items: center; border: 1px solid #ddd;">
                <span style="font-weight: bold; color: #333; margin-right: auto;">Ações em Lote:</span>
                <input type="text" name="observacao" placeholder="Obs (Opcional p/ Aprovar, Obrigatório p/ Rejeitar)" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 350px;">
                <button type="submit" name="acao" value="aprovar" class="btn btn-success" style="padding: 10px 20px; font-weight: bold;">✅ Aprovar Selecionados</button>
                <button type="submit" name="acao" value="rejeitar" class="btn btn-danger" onclick="return confirm('Deseja REJEITAR e devolver os itens selecionados?')" style="padding: 10px 20px;">❌ Rejeitar Selecionados</button>
            </div>

            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                        <th style="padding: 12px; width: 40px; text-align: center;">
                            <input type="checkbox" id="checkAll" onclick="toggleCheckboxes(this)" style="transform: scale(1.3); cursor: pointer;" checked>
                        </th>
                        <th style="padding: 12px;">ID / Prioridade</th>
                        <th style="padding: 12px; color: #6f42c1;">Ordem de Pagamento (OP) / RAP</th>
                        <th style="padding: 12px;">Doc / Fornecedor</th>
                    </tr>
                    <?php foreach ($itens as $item): 
                        $is_rejeitado = str_contains($item['status_atual'] ?? '', 'REJEITADO');
                    ?>
                    <tr style="border-bottom: 1px solid #eee; <?= $item['prioridade'] ? 'background: #fff5f5;' : '' ?>">
                        <td style="padding: 12px; text-align: center;">
                            <input type="checkbox" name="itens_selecionados[]" value="<?= $item['id'] ?>" class="item-checkbox" style="transform: scale(1.3); cursor: pointer;" checked>
                        </td>
                        
                        <td style="padding: 12px;">
                            <span style="background: #002244; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-family: monospace; font-size: 1.1em; border: 1px solid #001122;">
                                #<?= str_pad($item['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <br>
                            <?php if ($item['prioridade']): ?>
                                <span style="display:inline-block; margin-top:5px; color:#dc3545; font-weight:bold; font-size:0.85em;">🚩 URGENTE</span>
                            <?php endif; ?>
                            
                            <?php if ($is_rejeitado): ?>
                                <span style="display:inline-block; margin-top:5px; background: #dc3545; color: #fff; padding: 2px 5px; border-radius: 3px; font-size: 0.75em; font-weight: bold;">🚨 REJEITADO</span>
                            <?php endif; ?>
                        </td>

                        <td style="padding: 12px;">
                            <code style="font-size: 1.4em; color: #6f42c1; background: #f3f0f7; padding: 4px 10px; border-radius: 4px; border: 1px solid #d8cde9; font-weight:bold;">
                                <?= htmlspecialchars($item['op_numero'] ?? 'S/N') ?>
                            </code>
                            <br>
                            <?php if (!empty($item['numero_rap'])): ?>
                                <small style="color: #666; font-weight:bold; display:inline-block; margin-top:6px;">Capa: <?= htmlspecialchars($item['numero_rap']) ?></small>
                            <?php else: ?>
                                <small style="color: #999; display:inline-block; margin-top:6px;">Documento Isolado (Sem RAP)</small>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px;">
                            NF: <b><?= htmlspecialchars($item['num_documento_fiscal']) ?></b><br>
                            <small>CNPJ: <?= htmlspecialchars($item['cpf_cnpj']) ?></small>
                            <?php if (!empty($item['ns_numero'])): ?>
                                <br><span style="background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold; margin-top:4px; display:inline-block;">NS: <?= htmlspecialchars($item['ns_numero']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleCheckboxes(source) {
    checkboxes = document.getElementsByClassName('item-checkbox');
    for(var i=0, n=checkboxes.length;i<n;i++) { checkboxes[i].checked = source.checked; }
}
document.getElementById('form-assinatura').addEventListener('submit', function(e) {
    var checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) { e.preventDefault(); alert('Selecione pelo menos um item para processar na tabela.'); }
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
