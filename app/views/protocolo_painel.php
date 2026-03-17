<?php
$page_title = 'Protocolo Geral - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';

use App\core\Database;
$db = Database::getConnection();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #6f42c1; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div>
        <h2 style="margin: 0; color: #002244;">Setor de Protocolo</h2>
        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Triagem e Recebimento de Malotes OMAP/Setores</p>
    </div>
</div>

<?php if(isset($_GET['sucesso'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; border: 1px solid #c3e6cb;">
        ✅ Lote encaminhado com sucesso para a Execução Financeira (Operadores).
    </div>
<?php endif; ?>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #002244;">Lotes Aguardando Triagem Física/Virtual</h3>
    
    <?php if(empty($des)): ?>
        <p style="color: #666;">Caixa de entrada vazia. Nenhum lote aguardando no Protocolo.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 12px;">Origem</th>
                    <th style="padding: 12px;">ID Lote</th>
                    <th style="padding: 12px;">Data de Chegada</th>
                    <th style="padding: 12px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($des as $de): 
                    $stmt = $db->prepare("SELECT * FROM itens_de WHERE de_id = ?");
                    $stmt->execute([$de['id']]);
                    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">
                        <b><?= htmlspecialchars($de['unit_omap'] ?: 'SETOR INTERNO') ?></b><br>
                        <small style="color: #666;"><?= htmlspecialchars($de['criador_nome']) ?></small>
                    </td>
                    <td style="padding: 12px;"><code style="color: #004488; font-size: 1.1em;">#<?= str_pad($de['id'], 5, '0', STR_PAD_LEFT) ?></code></td>
                    <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($de['criado_em'])) ?></td>
                    <td style="padding: 12px; text-align: center;">
                        <button onclick="toggleItens(<?= $de['id'] ?>)" style="background: #6f42c1; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            Conferir Itens ⬇️
                        </button>
                    </td>
                </tr>
                <tr id="itens_<?= $de['id'] ?>" style="display: none; background: #faf8ff;">
                    <td colspan="4" style="padding: 15px; border-bottom: 3px solid #6f42c1;">
                        <div style="border-left: 4px solid #6f42c1; padding-left: 15px;">
                            <h4 style="margin: 0 0 10px 0; color: #333;">Itens contidos neste Lote:</h4>
                            <ul style="margin: 0 0 15px 0; padding-left: 20px;">
                                <?php foreach($itens as $item): ?>
                                    <li style="margin-bottom: 5px;">
                                        📄 <b>NF:</b> <?= htmlspecialchars($item['nf']) ?> | 
                                        <b>Credor:</b> <?= htmlspecialchars($item['cnpj']) ?> | 
                                        <b>Valor:</b> R$ <?= number_format($item['valor'], 2, ',', '.') ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <form action="/protocolo/encaminhar" method="POST" onsubmit="return confirm('Confirma o envio deste lote para a Execução Financeira?')">
                                <input type="hidden" name="de_id" value="<?= $de['id'] ?>">
                                <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                                    ➡️ Recebido. Encaminhar para Execução Financeira
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function toggleItens(id) {
    var el = document.getElementById('itens_' + id);
    el.style.display = (el.style.display === 'none') ? 'table-row' : 'none';
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>