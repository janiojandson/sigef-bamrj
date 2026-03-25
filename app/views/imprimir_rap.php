<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Imprimir <?= htmlspecialchars($rap['numero_rap'] ?? 'RAP') ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .cabecalho { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .tabela-rap { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabela-rap th, .tabela-rap td { border: 1px solid #000; padding: 10px; text-align: left; }
        .tabela-rap th { background-color: #f0f0f0; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 15px; font-size: 16px; background: #004488; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">🖨️ Imprimir Capa</button>
        <a href="/operador/fila?tab=rap" style="padding: 10px 15px; font-size: 16px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; margin-left: 10px; font-weight: bold;">⬅️ Voltar à Fila</a>
    </div>
    
    <div class="cabecalho">
        <h2>MARINHA DO BRASIL</h2>
        <h3>BASE DE ABASTECIMENTO DA MARINHA NO RIO DE JANEIRO</h3>
        <h1>RELATÓRIO DE AUTORIZAÇÃO DE PAGAMENTO (RAP)</h1>
        <p><b>Número do Lote:</b> <?= htmlspecialchars($rap['numero_rap']) ?> | <b>Gerado em:</b> <?= date('d/m/Y H:i', strtotime($rap['criado_em'])) ?></p>
    </div>

    <table class="tabela-rap">
        <tr>
            <th>Documento / CNPJ</th>
            <th>NS (OMAP)</th>
            <th>NP</th>
            <th>LF</th>
            <th>OP</th>
        </tr>
        <?php foreach ($itens as $i): ?>
        <tr>
            <td>NF: <b><?= htmlspecialchars($i['num_documento_fiscal'] ?? '') ?></b><br><small><?= htmlspecialchars($i['cpf_cnpj'] ?? '') ?></small></td>
            <td><b><?= htmlspecialchars($i['ns_numero'] ?? '-') ?></b></td>
            <td><?= htmlspecialchars($i['np_numero'] ?? '-') ?></td>
            <td><?= htmlspecialchars($i['lf_numero'] ?? '-') ?></td>
            <td><b><?= htmlspecialchars($i['op_numero'] ?? '-') ?></b></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div style="margin-top: 80px; text-align: center; display: flex; justify-content: space-around;">
        <div style="border-top: 1px solid #000; width: 30%; padding-top: 5px;">Assinatura Gestor Financeiro</div>
        <div style="border-top: 1px solid #000; width: 30%; padding-top: 5px;">Assinatura Diretor</div>
    </div>
</body>
</html>