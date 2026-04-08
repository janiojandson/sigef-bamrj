<?php $page_title = 'Monitoramento Global - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📊 Monitoramento Global e RAPs</h2>
    </div>
    <div>
        <button onclick="filtrarStatus('ARQUIVADO')" class="btn btn-success">🗄️ Ver Arquivados</button>
        <button onclick="filtrarStatus('')" class="btn btn-info">🔄 Ver Ativos</button>
        <a href="/" class="btn btn-secondary" style="margin-left: 5px;">⬅️ Dashboard</a>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; border-left: 5px solid #ffcc00;">
    <h3 style="margin-top: 0; color: #333;">🖨️ RAPs Gerados (Prontos para Impressão)</h3>
    <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px;">
        <?php if(!empty($raps)): foreach($raps as $rap): ?>
            <div style="background: #f8f9fa; border: 1px solid #ccc; padding: 10px; border-radius: 4px; display: flex; flex-direction: column; align-items: center; min-width: 150px;">
                <a href="/operador/imprimir_rap?id=<?= $rap['id'] ?>" target="_blank" style="text-decoration: none; color: #004488; font-weight: bold; text-align: center; margin-bottom: 8px;">
                    📄 <?= htmlspecialchars($rap['numero_rap']) ?><br>
                    <small style="color: #666;"><?= date('d/m', strtotime($rap['criado_em'])) ?></small>
                </a>
                <a href="/operador/excluir_rap?id=<?= $rap['id'] ?>" onclick="return confirm('Deseja cancelar este RAP? Apenas as OPs que AINDA NÃO foram assinadas voltarão para sua fila de geração. OPs que já avançaram não serão afetadas.')" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8em; text-decoration: none;">❌ Cancelar RAP</a>
            </div>
        <?php endforeach; else: ?>
            <span style="color: #666;">Nenhum RAP gerado ainda.</span>
        <?php endif; ?>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="margin-bottom: 15px;">
        <input type="text" id="filtroMonitoramento" onkeyup="filtrarTabela()" placeholder="🔍 Filtrar por ID, CNPJ, DE, OP ou Status..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em;">
    </div>

    <div class="table-responsive">
        <table id="tabelaMonitoramento" style="width: 100%; border-collapse: collapse; min-width: 900px; font-size: 0.9em;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                <th style="padding: 12px;">ID / Origem (DE)</th>
                <th style="padding: 12px;">CNPJ / Doc / NS</th>
                <th style="padding: 12px;">Dados Sistêmicos</th>
                <th style="padding: 12px; color: #004488;">Posição / Fila Atual</th>
            </tr>
            <?php foreach ($itens_ativos as $i): 
                $is_rejeitado = str_contains($i['status_atual'] ?? '', 'REJEITADO');
            ?>
            <tr class="linha-item" data-status="<?= htmlspecialchars($i['status_atual']) ?>" style="border-bottom: 1px solid #eee; <?= $i['status_atual'] === 'ARQUIVADO' ? 'display: none;' : '' ?>">
                
                <td style="padding: 12px;">
                    <span style="background: #002244; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-family: monospace; font-size: 1.1em; border: 1px solid #001122;">
                        #<?= str_pad($i['id'], 5, '0', STR_PAD_LEFT) ?>
                    </span>
                    
                    <?php if ($is_rejeitado): ?>
                        <br><span style="display: inline-block; background: #dc3545; color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; margin-top: 5px;">
                            🚨 REJEITADO
                        </span>
                    <?php endif; ?>

                    <br><small style="color: #666; margin-top: 5px; display: inline-block;">DE: <?= htmlspecialchars($i['numero_geral']) ?></small>
                    <br><small style="color: #999;"><?= htmlspecialchars($i['origem_tipo']) ?></small>
                </td>
                
                <td style="padding: 12px;">
                    <span style="color:#004488; font-weight:bold; font-size:0.9em;"><?= htmlspecialchars($i['empresa_nome'] ?? 'Não Informado') ?></span><br>
                    <small>CNPJ: <?= htmlspecialchars($i['cpf_cnpj']) ?></small><br>
                    NF: <b><?= htmlspecialchars($i['num_documento_fiscal']) ?></b>
                    <?php if (!empty($i['ns_numero'])): ?>
                        <br><span style="background:#ffcc00; color:#002244; padding:2px 4px; border-radius:3px; font-size:0.85em; font-weight:bold; margin-top:4px; display:inline-block;">NS: <?= htmlspecialchars($i['ns_numero']) ?></span>
                    <?php endif; ?>
                </td>
                
                <td style="padding: 12px; line-height: 1.4;">
                    <?php if($i['np_numero']) echo "NP: {$i['np_numero']}<br>"; ?>
                    <?php if($i['lf_numero']) echo "LF: {$i['lf_numero']}<br>"; ?>
                    <?php if($i['op_numero']) echo "OP: <b style='color:#6f42c1; font-size:1.1em;'>{$i['op_numero']}</b>"; ?>
                </td>
                
                <td style="padding: 12px;">
                    <span class="badge" style="background: <?= $is_rejeitado ? '#dc3545; color: white;' : '#e2e3e5; color: #002244;' ?>; padding: 5px; border-radius: 4px; font-weight: bold;">
                        <?= str_replace('_', ' ', htmlspecialchars($i['status_atual'])) ?>
                    </span>
                    <div style="font-size: 0.85em; color: <?= $is_rejeitado ? '#dc3545' : '#666' ?>; margin-top: 5px;">
                        Obs: <?= htmlspecialchars(explode(':', $i['observacao_atual'])[1] ?? 'Avanço de fase') ?>
                    </div>

                    <?php if ($i['status_atual'] === 'ARQUIVADO' && $_SESSION['role'] === 'Operador'): ?>
                        <div style="margin-top: 15px; padding: 10px; border: 1px dashed #dc3545; border-radius: 4px; background: #fff5f5;">
                            <form action="/operador/acao" method="POST" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja cancelar esta OB e reiniciar o processo de liquidação? Isso ficará gravado na auditoria e a NF voltará para sua fila inicial.')" style="margin: 0;">
                                <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                                <input type="hidden" name="tipo_acao" value="estornar_ob">
                                <div style="font-size: 0.8em; color: #dc3545; font-weight: bold; margin-bottom: 5px;">⚠️ Cancelar OB:</div>
                                <input type="text" name="observacao" placeholder="Motivo do erro (ex: Domicílio Inválido)" required style="width: 100%; padding: 6px; border: 1px solid #dc3545; border-radius: 4px; font-size: 0.85em; box-sizing: border-box; margin-bottom: 5px;">
                                <button type="submit" class="btn btn-danger" style="width: 100%; font-size: 0.85em;">🔄 Estornar e Reiniciar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function filtrarTabela() {
    var input = document.getElementById("filtroMonitoramento");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tabelaMonitoramento");
    var tr = table.getElementsByClassName("linha-item");
    for (var i = 0; i < tr.length; i++) {
        if (tr[i].getAttribute('data-status') === 'ARQUIVADO' && filter === "") {
            tr[i].style.display = "none";
            continue;
        }
        var tdText = tr[i].innerText || tr[i].textContent;
        if (tdText.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function filtrarStatus(statusAlvo) {
    var tr = document.getElementsByClassName("linha-item");
    for (var i = 0; i < tr.length; i++) {
        var statusItem = tr[i].getAttribute('data-status');
        if (statusAlvo === '') {
            tr[i].style.display = statusItem === 'ARQUIVADO' ? "none" : "";
        } else {
            tr[i].style.display = statusItem === statusAlvo ? "" : "none";
        }
    }
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
