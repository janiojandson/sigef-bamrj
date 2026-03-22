<?php
$page_title = 'Dashboard - SIGEF BAMRJ';
require __DIR__ . '/partials/header.php';
$role = $_SESSION['role'];
?>

<div id="alerta-novo-doc" style="display: none; background: #ffcc00; color: #002244; padding: 12px; text-align: center; font-weight: bold; margin-bottom: 20px; border-radius: 5px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 2px solid #e6b800;" onclick="location.reload()">
    🔔 ATENÇÃO: Há novos documentos na sua caixa de entrada. Clique para atualizar a tela.
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; border-left: 5px solid #004488;">
    <div>
        <h3 style="margin: 0; color: #002244;">Painel Principal - Perfil: <span style="color: #666;"><?= htmlspecialchars($role) ?></span></h3>
        <p style="margin: 5px 0 0 0; color: #555; font-size: 0.9em;">Setor Operacional: <b><?= htmlspecialchars($_SESSION['origem_setor']) ?></b></p>
    </div>
    
    <?php if ($role !== 'Admin'): ?>
    <form action="/" method="GET" style="display: flex; gap: 5px;">
        <input type="text" name="q" placeholder="Buscar DE ou CNPJ..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="padding: 10px; border: 1px solid #ccc; width: 250px; border-radius: 4px; font-weight: bold;">
        <button type="submit" style="padding: 10px 15px; background: #004488; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🔍 Pesquisar</button>
    </form>
    <?php endif; ?>
</div>

<?php if ($role === 'Admin'): ?>
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="color: #002244;">Área Restrita Administrativa</h2>
        <p style="color: #666; font-size: 1.1em; max-width: 600px; margin: 0 auto 20px auto;">O perfil de Administrador tem acesso exclusivo à gestão de usuários e segurança orgânica. A visualização de processos financeiros não é permitida para este nível.</p>
        <a href="/admin/users" style="background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 1.1em; display: inline-block;">⚙️ Acessar Gestão de Usuários</a>
    </div>

<?php else: ?>
    
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <?php if (in_array($role, ['Operador', 'Protocolo'])): ?>
            <a href="/protocolo/fila" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">📥 Fila do Protocolo</a>
        <?php endif; ?>

        <?php if (!in_array($role, ['Diretor', 'Vice_Diretor', 'Chefe_Departamento'])): ?>
            <a href="/de/nova" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">➕ Lançar Nova DE</a>
        <?php endif; ?>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <?php if (isset($_GET['q']) && !empty($_GET['q'])): ?>
            <h3 style="margin-top:0; color: #002244; border-bottom: 2px solid #eee; padding-bottom: 10px;">🔍 Resultados da Busca: <?= htmlspecialchars($_GET['q']) ?></h3>
            <p style="color: #666;">(A implementação da listagem de busca será conectada à Controller no próximo passo).</p>
        <?php else: ?>
            <h3 style="margin-top:0; color: #002244; border-bottom: 2px solid #eee; padding-bottom: 10px;">📥 Sua Caixa de Entrada / Lotes Recentes</h3>
            <p style="color: #666;">(Aqui injetaremos as queries personalizadas por perfil: OMAP vê seus rejeitados, Operador vê abas, Assinador vê sua fila).</p>
        <?php endif; ?>
    </div>

<?php endif; ?>

<script>
let checkInterval = 30000; // Checa a cada 30 segundos
if ("<?= $role ?>" !== 'Admin') {
    setInterval(function() {
        fetch('/api/check_inbox?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                // Se a API retornar que há mais documentos na caixa do que a página atual conhece (lógica a refinar no Controller)
                if (data.count > 0) {
                    const alerta = document.getElementById('alerta-novo-doc');
                    alerta.style.display = 'block';
                }
            })
            .catch(err => console.error("Falha de Comunicação no Radar:", err));
    }, checkInterval);
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>