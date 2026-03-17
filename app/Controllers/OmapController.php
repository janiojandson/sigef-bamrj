<?php
namespace App\Controllers;

use App\core\Database;
use PDO;
use Exception;

class OmapController {
    
    // Carrega o Painel da OMAP com suas DEs e Itens
    public function painel() {
        if ($_SESSION['role'] !== 'OMAP' && $_SESSION['role'] !== 'SETOR_INTERNO') {
            die("Acesso negado. Apenas OMAP ou Setores Internos.");
        }

        $db = Database::getConnection();
        
        // Busca as Capas (DEs) criadas por este usuário
        $stmt = $db->prepare("SELECT * FROM documentos_encaminhamento WHERE criado_por = ? ORDER BY criado_em DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $des = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para simplificar nesta etapa, se a view não existir, mostramos um JSON tático
        if (!file_exists(__DIR__ . '/../views/omap_painel.php')) {
            echo "<h2>Módulo OMAP Carregado com Sucesso! (View em construção)</h2>";
            echo "<pre>" . print_r($des, true) . "</pre>";
            exit();
        }

        require __DIR__ . '/../views/omap_painel.php';
    }

    // Recebe o formulário de criação de uma nova Capa (DE) e seus itens
    public function criarDE() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            
            $origem = $_SESSION['role'];
            $ns_pa = $_POST['ns_pa'] ?? null; // Apenas OMAP envia isso
            $nomes_documentos = $_POST['nomes_documentos'] ?? []; // Array de nomes de NF
            
            try {
                $db->beginTransaction();

                // 1. Cria a Capa (DE)
                $stmtDE = $db->prepare("INSERT INTO documentos_encaminhamento (criado_por, origem, status_geral) VALUES (?, ?, 'ENVIADO_PROTOCOLO') RETURNING id");
                $stmtDE->execute([$_SESSION['user_id'], $origem]);
                $de_id = $stmtDE->fetchColumn();

                // 2. Insere os itens granulares (NFs, Recibos, etc)
                $stmtItem = $db->prepare("INSERT INTO itens_de (de_id, nome_documento, ns_pa, status_item) VALUES (?, ?, ?, 'ENVIADO_PROTOCOLO')");
                
                foreach ($nomes_documentos as $nome) {
                    if (!empty(trim($nome))) {
                        $stmtItem->execute([$de_id, trim($nome), $ns_pa]);
                    }
                }

                // 3. Grava na Auditoria
                $stmtAudit = $db->prepare("INSERT INTO auditoria (de_id, usuario_nome, perfil, acao, justificativa) VALUES (?, ?, ?, 'CRIACAO_DE', 'Lote de documentos enviado pela OMAP/Setor')");
                $stmtAudit->execute([$de_id, $_SESSION['name'], $_SESSION['role']]);

                $db->commit();
                header("Location: /omap/painel?sucesso=1");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                die("Erro tático ao criar DE: " . $e->getMessage());
            }
        }
    }
}