<?php
// Arquivo: public/patch.php
require_once __DIR__ . '/../app/core/Database.php';
use App\core\Database;

try {
    $db = Database::getConnection();
    
    // 1. Força a coluna origem na Capa (caso não exista)
    try {
        $db->exec("ALTER TABLE documentos_encaminhamento ADD COLUMN origem VARCHAR(64) DEFAULT 'OMAP'");
    } catch (Exception $e) { /* Ignora se já existir */ }

    // 2. Adiciona os campos granulares na tabela de Itens
    try {
        $db->exec("ALTER TABLE itens_de ADD COLUMN nf VARCHAR(128)");
        $db->exec("ALTER TABLE itens_de ADD COLUMN cnpj VARCHAR(32)");
        $db->exec("ALTER TABLE itens_de ADD COLUMN valor DECIMAL(15,2)");
        // Removemos ns_pa antiga se existir e garantimos a nova ns (opicional, vamos apenas usar ns_pa)
    } catch (Exception $e) { /* Ignora se já existir */ }

    echo "<h2 style='color:green;'>✅ PATCH APLICADO COM SUCESSO! Banco de dados atualizado para receber NF, CNPJ, Valor e a Rota do Protocolo.</h2>";
    echo "<a href='/dashboard'>Voltar ao Sistema</a>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}