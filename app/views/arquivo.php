<?php
$page_title = 'Arquivo Geral - Assinador BAMRJ';
require __DIR__ . '/partials/header.php';

$archiveController = new \App\Controllers\ArchiveController();
$dados = $archiveController->getArchiveData();

$role = $dados['role'];
$search_query = $dados['search_query'];
$documents = $dados['documents'];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #6c757d; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="margin: 0; color: #002244;">🗄️ Arquivo Geral de Processos</h3>
        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Consulta de Notas de Empenho e Processos Finalizados</p>
    </div>
    
    <div style="display: flex; gap: 10px; width: 100%; justify-content: flex-end; align-items: center;">
        <?php if ($role !== 'Usuário Comum'): ?>
            <a href="/" style="background: #004488; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 15px;">⬅️ Voltar</a>
        <?php endif; ?>
        
        <form action="/arquivo" method="GET" style="display: flex; gap: 5px;">
            <select name="ano" id="filtro-ano-arq" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-weight: bold; color: #002244;"></select>
            <input type="text" name="q" placeholder="Buscar por SOLEMP, CNPJ/CPF, Nome..." value="<?= htmlspecialchars($search_query) ?>" style="padding: 10px; border: 1px solid #ccc; width: 280px; border-radius: 4px;">
            <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🔍 Pesquisar</button>
        </form>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    
    <?php if ($role === 'Usuário Comum' && empty($documents) && empty($search_query)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <h2 style="color: #002244; margin-bottom: 15px;">Bem-vindo à Consulta Pública</h2>
            <p style="color: #666; font-size: 1.1em; max-width: 600px; margin: 0 auto;">Utilize a barra de pesquisa acima para buscar Notas de Empenho e Processos Finalizados através do CPF/CNPJ ou número da SOLEMP.</p>
        </div>
        
    <?php elseif (empty($documents) && !empty($search_query)): ?>
        <div style="text-align: center; padding: 40px; background: #f8d7da; border-radius: 5px; color: #721c24;">
            <h3 style="margin: 0;">Nenhum processo finalizado encontrado para a busca "<?= htmlspecialchars($search_query) ?>".</h3>
        </div>
        
    <?php elseif (empty($documents)): ?>
        <h3 style="text-align: center; color: #666; padding: 40px 0;">O arquivo está vazio para o ano selecionado.</h3>
        
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd; text-align: left;">
                        <th style="padding: 12px; color: #002244;">Protocolo</th>
                        <th style="padding: 12px; color: #002244;">Assunto</th>
                        <th style="padding: 12px; color: #002244;">CPF/CNPJ</th>
                        <th style="padding: 12px; color: #002244;">SOLEMP</th> 
                        <th style="padding: 12px; color: #002244;">Status Final</th>
                        <th style="padding: 12px; text-align: right; color: #002244;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr style="border-bottom: 1px solid #eee; transition: 0.2s;">
                        <td style="padding: 12px;"><code style="color: #d32f2f; font-weight: bold;"><?= htmlspecialchars($doc['protocol']) ?></code></td>
                        <td style="padding: 12px;"><b><?= htmlspecialchars($doc['name']) ?></b></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($doc['cpf_cnpj']) ?: '-' ?></td>
                        <td style="padding: 12px;"><strong><?= htmlspecialchars($doc['solemp']) ?: '-' ?></strong></td> 
                        <td style="padding: 12px;">
                            <?php
                            $statusBg = '#e2e3e5'; $statusColor = '#383d41';
                            if (in_array($doc['status'], ['Cancelado', 'Anulado'])) { $statusBg = '#343a40'; $statusColor = 'white'; }
                            elseif ($doc['status'] === 'Reforçado') { $statusBg = '#17a2b8'; $statusColor = 'white'; }
                            elseif ($doc['status'] === 'Arquivado') { $statusBg = '#28a745'; $statusColor = 'white'; }
                            ?>
                            <span style="font-size: 0.85em; padding: 6px 10px; border-radius: 4px; font-weight: bold; background: <?= $statusBg ?>; color: <?= $statusColor ?>;">
                                <?= htmlspecialchars($doc['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="/view?id=<?= $doc['id'] ?>" style="background: #004488; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9em; display: inline-block;">📄 Visualizar PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
const selectAnoArq = document.getElementById('filtro-ano-arq');
const anoAtualArq = new Date().getFullYear();
const urlParamsArq = new URLSearchParams(window.location.search);
const anoPesqArq = urlParamsArq.get('ano');

for (let ano = 2026; ano <= Math.max(2026, anoAtualArq); ano++) {
    let opt = document.createElement('option');
    opt.value = ano; opt.innerHTML = ano;
    if (anoPesqArq && parseInt(anoPesqArq) === ano) opt.selected = true; 
    else if (!anoPesqArq && ano === anoAtualArq) opt.selected = true;
    selectAnoArq.appendChild(opt);
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>