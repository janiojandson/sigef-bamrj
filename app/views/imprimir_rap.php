<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Imprimir <?= $rap['numero_rap'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .cabecalho { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .tabela-rap { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabela-rap th, .tabela-rap td { border: 1px solid #000; padding: 10px; text-align: left; }
        .tabela-rap th { background-color: #f0f0f0; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="padding: 10px; font-size: 16px; background: #004488; color: white; border: none; cursor: pointer; margin-bottom: 20px;">🖨️ Imprimir Capa</button>
    
    <div class="cabecalho">
        <h2>MARINHA DO BRASIL</h2>
        <h3>BASE DE ABASTECIMENTO DA MARINHA NO RIO DE JANEIRO</h3>
        <h1>RELATÓRIO DE AUTORIZAÇÃO DE PAGAMENTO (RAP)</h1>
        <p><b>Número do Lote:</b> <?= htmlspecialchars($rap['numero_rap']) ?> | <b>Gerado em:</b> <?= date('d/m/Y H:i', strtotime($rap['criado_em'])) ?></p>
    </div>

    <table class="tabela-rap">
        <tr>
            <th>Documento / CNPJ</th>
            <th>PA</th>
            <th>NP</th>
            <th>LF</th>
            <th>OP</th>
            <th>Valor (R$)</th>
        </tr>
        <?php $soma = 0; foreach ($itens as $i): $soma += $i['valor_total']; ?>
        <tr>
            <td>NF: <?= htmlspecialchars($i['num_documento_fiscal']) ?><br><small><?= htmlspecialchars($i['cpf_cnpj']) ?></small></td>
            <td><?= htmlspecialchars($i['pa_numero']) ?></td>
            <td><?= htmlspecialchars($i['np_numero']) ?></td>
            <td><?= htmlspecialchars($i['lf_numero']) ?></td>
            <td><b><?= htmlspecialchars($i['op_numero']) ?></b></td>
            <td>R$ <?= number_format($i['valor_total'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="5" style="text-align: right;"><b>TOTAL DESTE RAP:</b></td>
            <td><b>R$ <?= number_format($soma, 2, ',', '.') ?></b></td>
        </tr>
    </table>

    <div style="margin-top: 80px; text-align: center; display: flex; justify-content: space-around;">
        <div style="border-top: 1px solid #000; width: 30%; padding-top: 5px;">Assinatura Gestor Financeiro</div>
        <div style="border-top: 1px solid #000; width: 30%; padding-top: 5px;">Assinatura Diretor</div>
    </div>
</body>
</html>