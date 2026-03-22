<?php $page_title = 'Monitoramento Global - SIGEF'; require __DIR__ . '/partials/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="margin: 0; color: #002244;">📊 Monitoramento Global e RAPs</h2>
    </div>
    <div>
        <button onclick="filtrarStatus('ARQUIVADO')" style="background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">🗄️ Ver Arquivados</button>
        <button onclick="filtrarStatus('')" style="background: #17a2b8; color: white; padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">🔄 Ver Ativos</button>
        <a href="/" style="background: #6c757d; color: white; padding: 9px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 5px;">⬅️ Dashboard</a>
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
                <a href="/operador/excluir_rap?id=<?= $rap['id'] ?>" onclick="return confirm('Deseja excluir este RAP? As OPs voltarão para a sua fila de geração.')" style="background: #dc3545; color: white; padding: 4px 8px; font-size: 0.8em; border-radius: 4px; text-decoration: none;">❌ Excluir</a>
            </div>
        <?php endforeach; else: ?>
            <span style="color: #666;">Nenhum RAP gerado ainda.</span>
        <?php endif; ?>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="margin-bottom: 15px;">
        <input type="text" id="filtroMonitoramento" onkeyup="filtrarTabela()" placeholder="🔍 Filtrar por CNPJ, DE, Documento ou Status..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em;">
    </div>

    <div class="table-responsive">
        <table id="tabelaMonitoramento" style="width: 100%; border-collapse: collapse; min-width: 900px; font-size: 0.9em;">
            <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                <th style="padding: 12px;">DE / Origem</th>
                <th style="padding: 12px;">CNPJ / Doc</th>
                <th style="padding: 12px;">Dados Sistêmicos</th>
                <th style="padding: 12px; color: #004488;">Posição / Fila Atual</th>
            </tr>
            <?php foreach ($itens_ativos as $i): ?>
            <tr class="linha-item" data-status="<?= htmlspecialchars($i['status_atual']) ?>" style="border-bottom: 1px solid #eee; <?= $i['status_atual'] === 'ARQUIVADO' ? 'display: none;' : '' ?>">
                <td style="padding: 12px;">
                    <b><?= htmlspecialchars($i['numero_geral']) ?></b><br>
                    <small style="color: #666;"><?= htmlspecialchars($i['origem_tipo']) ?></small>
                </td>
                <td style="padding: 12px;">
                    <?= htmlspecialchars($i['cpf_cnpj']) ?><br>
                    NF: <b><?= htmlspecialchars($i['num_documento_fiscal']) ?></b>
                </td>
                <td style="padding: 12px; line-height: 1.4;">
                    <?php if($i['pa_numero']) echo "PA: {$i['pa_numero']}<br>"; ?>
                    <?php if($i['np_numero']) echo "NP: {$i['np_numero']}<br>"; ?>
                    <?php if($i['lf_numero']) echo "LF: {$i['lf_numero']}<br>"; ?>
                    <?php if($i['op_numero']) echo "OP: <b style='color:#6f42c1'>{$i['op_numero']}</b>"; ?>
                </td>
                <td style="padding: 12px;">
                    <span style="background: #e2e3e5; color: #002244; font-weight: bold; padding: 4px 8px; border-radius: 4px;">
                        <?= str_replace('_', ' ', htmlspecialchars($i['status_atual'])) ?>
                    </span>
                    <div style="font-size: 0.85em; color: #666; margin-top: 5px;">
                        Obs: <?= htmlspecialchars(explode(':', $i['observacao_atual'])[1] ?? 'Avanço de fase') ?>
                    </div>
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
        // Ignora os arquivados se a caixa de texto estiver limpando
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
            // Mostrar Ativos (oculta arquivados)
            tr[i].style.display = statusItem === 'ARQUIVADO' ? "none" : "";
        } else {
            // Mostrar apenas o status alvo
            tr[i].style.display = statusItem === statusAlvo ? "" : "none";
        }
    }
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>