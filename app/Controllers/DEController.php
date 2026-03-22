<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;
use Exception;

class DEController {

    // Renderiza a página de criação
    public function create() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        require __DIR__ . '/../views/de_create.php';
    }

    // Processa o formulário de inserção
    public function store() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            
            // Dados Básicos
            $origem = $_POST['origem'] ?? 'BAMRJ';
            $cpf_cnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $num_doc = trim($_POST['num_doc_fiscal'] ?? '');
            
            // Tratamento de Moeda (Ex: 1.500,75 -> 1500.75)
            $valor_bruto = $_POST['valor_total'] ?? '0';
            $valor_total = str_replace(['.', ','], ['', '.'], $valor_bruto);
            
            $pa_numero = ($origem === 'OMAP') ? trim($_POST['pa_numero'] ?? '') : null;
            $observacao = trim($_POST['observacao'] ?? 'Lançamento inicial.');
            
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp_msg = date('d/m/Y H:i');
            $obs_formatada = "[{$timestamp_msg} - {$perfil}]: DE Criada - \"{$observacao}\"";

            // Geração de Número Geral da DE (Ex: DE-2026-ABCD)
            $hash = strtoupper(substr(uniqid(), -4));
            $numero_geral_de = "DE-" . date('Y') . "-" . $hash;

            try {
                $db->beginTransaction();

                // 1. Cria a DE (Capa/Lote)
                $stmtLote = $db->prepare("INSERT INTO de_lotes (numero_geral, origem_tipo, criado_por) VALUES (?, ?, ?) RETURNING id");
                $stmtLote->execute([$numero_geral_de, $origem, $usuario]);
                $lote_id = $stmtLote->fetchColumn();

                // 2. Insere o Primeiro Item desta DE
                $stmtItem = $db->prepare("INSERT INTO de_itens (lote_id, cpf_cnpj, num_documento_fiscal, valor_total, pa_numero, status_atual, observacao_atual) 
                                          VALUES (?, ?, ?, ?, ?, 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?) RETURNING id");
                $stmtItem->execute([$lote_id, $cpf_cnpj, $num_doc, $valor_total, $pa_numero, $obs_formatada]);
                $item_id = $stmtItem->fetchColumn();

                // 3. Registra o Evento (Auditoria Tática)
                $stmtEvento = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) 
                                            VALUES (?, ?, ?, 'CRIAR_DE', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?)");
                $stmtEvento->execute([$item_id, $usuario, $perfil, $observacao]);

                $db->commit();
                
                // Redireciona para o painel com sucesso
                header("Location: /index?msg=de_criada");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                die("<div style='background:#dc3545;color:white;padding:20px;'><h1>⚠️ Falha na Inserção</h1><p>" . $e->getMessage() . "</p></div>");
            }
        }
    }
}