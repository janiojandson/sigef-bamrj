<?php
$page_title = 'Dashboard - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';
?>

<div style="display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 5px solid #004488;">
        <h3 style="margin: 0; color: #666; font-size: 1em;">Perfil de Acesso</h3>
        <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #002244;"><?= htmlspecialchars($_SESSION['role']) ?></p>
    </div>
    <div style="flex: 1; min-width: 200px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 5px solid #ffcc00;">
        <h3 style="margin: 0; color: #666; font-size: 1em;">DEs em Elaboração</h3>
        <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #002244;"><?= $total_elaboracao ?></p>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px;">
    <h3 style="margin: 0; color: #002244;">🗂️ Controle de Documentos de Encaminhamento (DE)</h3>
    
    <div style="display: flex; gap: 10px;">
        <a href="/de/nova" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">➕ Lançar Nova DE</a>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <?php if (empty($lotes)): ?>
        <h4 style="text-align: center; color: #666; padding: 30px 0;">Nenhum Lote/DE encontrado na base de dados.</h4>
    <?php else: ?>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #002244; text-align: left;">
                        <th style="padding: 12px; color: #002244;">Número Geral (DE)</th>
                        <th style="padding: 12px; color: #002244;">Origem</th>
                        <th style="padding: 12px; color: #002244;">Status do Lote</th>
                        <th style="padding: 12px; color: #002244;">Criado em</th>
                        <th style="padding: 12px; color: #002244;">Criado por</th>
                        <th style="padding: 12px; text-align: center; color: #002244;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotes as $lote): ?>
                    <tr style="border-bottom: 1px solid #eee; transition: 0.2s;">
                        <td style="padding: 12px;"><code style="color: #d32f2f; font-weight: bold; font-size: 1.1em;"><?= htmlspecialchars($lote['numero_geral']) ?></code></td>
                        <td style="padding: 12px;"><b><?= htmlspecialchars($lote['origem_tipo']) ?></b></td>
                        <td style="padding: 12px;">
                            <span style="font-size: 0.85em; padding: 6px 10px; border-radius: 4px; font-weight: bold; background: #e2e3e5; color: #383d41;">
                                <?= htmlspecialchars($lote['status_lote']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($lote['criado_em'])) ?></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($lote['criado_por']) ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <button disabled style="background: #6c757d; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: not-allowed; font-size: 0.9em;">Abrir Itens</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>