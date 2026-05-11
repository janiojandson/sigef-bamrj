<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Capa de Protocolo - <?= htmlspecialchars($lote['numero_geral']) ?></title>
    <style>
        @page { 
            size: A4 portrait; 
            margin: 15mm; 
        }
        body { 
            font-family: 'Times New Roman', serif; 
            margin: 0; 
            padding: 0; 
            font-size: 11pt; 
            color: #000;
        }
        
        /* Cabeçalho */
        .cabecalho { 
            text-align: center; 
            border-bottom: 3px double #000; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .cabecalho h1 { 
            font-size: 14pt; 
            margin: 0 0 3px 0; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
        }
        .cabecalho h2 { 
            font-size: 11pt; 
            margin: 0 0 3px 0; 
            font-weight: normal; 
        }
        .cabecalho h3 { 
            font-size: 13pt; 
            margin: 10px 0 0 0; 
            text-transform: uppercase; 
        }
        
        /* Dados do Documento */
        .dados-doc { 
            margin-bottom: 20px; 
            padding: 10px; 
            border: 1px solid #000; 
            background: #f5f5f5; 
        }
        .dados-doc p { 
            margin: 3px 0; 
            font-size: 10pt; 
        }
        .dados-doc strong { 
            display: inline-block; 
            min-width: 120px; 
        }
        
        /* Tabela de Rubricas */
        .tabela-rubricas { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        .tabela-rubricas th, 
        .tabela-rubricas td { 
            border: 1px solid #000; 
            padding: 8px 10px; 
            text-align: left; 
            vertical-align: middle; 
        }
        .tabela-rubricas th { 
            background-color: #e0e0e0; 
            font-weight: bold; 
            font-size: 10pt; 
            text-align: center; 
        }
        .tabela-rubricas td { 
            font-size: 10pt; 
            min-height: 30px; 
        }
        .tabela-rubricas .linha-branca { 
            height: 40px; 
        }
        
        /* Rodapé */
        .rodape-capa {
            margin-top: 40px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        /* Botão de Impressão */
        .no-print { 
            margin-bottom: 20px; 
            text-align: center; 
            padding: 15px; 
            background: #f8f9fa; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
        }
        .no-print button, .no-print a {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
        }
        
        @media print { 
            .no-print { display: none !important; } 
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="background: #004488; color: white;">🖨️ Imprimir Capa</button>
        <a href="/protocolo/fila" style="background: #6c757d; color: white;">⬅️ Voltar à Fila</a>
    </div>
    
    <div class="cabecalho">
        <h1>Marinha do Brasil</h1>
        <h2>Base de Abastecimento da Marinha no Rio de Janeiro</h2>
        <h3>Controle de Protocolo</h3>
    </div>
    
    <div class="dados-doc">
        <p><strong>Item (ID):</strong> #<?= str_pad($itens[0]['id'], 5, '0', STR_PAD_LEFT) ?></p>
        <p><strong>Nota Fiscal / Doc:</strong> <?= htmlspecialchars($itens[0]['num_documento_fiscal']) ?></p>
        <?php if (!empty($itens[0]['ns_numero'])): ?>
            <p><strong>NS:</strong> <?= htmlspecialchars($itens[0]['ns_numero']) ?></p>
        <?php endif; ?>
        <p><strong>Fornecedor:</strong> <?= htmlspecialchars($itens[0]['empresa_nome'] ?? '') ?> (<?= htmlspecialchars($itens[0]['cpf_cnpj']) ?>)</p>
        <p><strong>DE / Lote (Origem):</strong> <?= htmlspecialchars($lote['numero_geral']) ?> (<?= htmlspecialchars($lote['origem_tipo']) ?>)</p>
        <p><strong>Data do Envio:</strong> <?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></p>
    </div>
    
    <table class="tabela-rubricas">
        <thead>
            <tr>
                <th style="width: 18%;">Origem</th>
                <th style="width: 14%;">Data</th>
                <th style="width: 12%;">Envio</th>
                <th style="width: 18%;">Rubrica</th>
                <th style="width: 38%;">Assunto</th>
            </tr>
        </thead>
        <tbody>
            <!-- Primeira Linha Automática -->
            <tr>
                <td style="text-align: center; font-weight: bold;">Protocolo</td>
                <td style="text-align: center;"><?= date('d/m/Y', strtotime($lote['criado_em'])) ?></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            
            <!-- Linhas em Branco para preenchimento manual -->
            <?php for ($i = 0; $i < 15; $i++): ?>
            <tr class="linha-branca">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    
    <div class="rodape-capa">
        Documento de uso interno — BAMRJ — Sistema Integrado de Gestão e Execução Financeira (SIGEF)
    </div>
</body>
</html>