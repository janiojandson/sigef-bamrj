<?php
namespace App\Controllers;

use App\core\Database;
use PDO;
use Exception;

class OmapController {
    
    public function painel() {
        if ($_SESSION['role'] !== 'OMAP' && $_SESSION['role'] !== 'SETOR_INTERNO') {
            die("Acesso negado.");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM documentos_encaminhamento WHERE criado_por = ? ORDER BY criado_em DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $des = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/omap_painel.php';
    }

    public function criarDE() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            
            $origem = $_SESSION['role'];
            // Arrays recebidos do formulário dinâmico
            $ns_list = $_POST['ns'] ?? [];
            $nf_list = $_POST['nf'] ?? [];
            $cnpj_list = $_POST['cnpj'] ?? [];
            $valor_list = $_POST['valor'] ?? [];
            
            try {
                $db->beginTransaction();

                // 1. Cria a Capa enviando para o PROTOCOLO
                $stmtDE = $db->prepare("INSERT INTO documentos_encaminhamento (criado_por, origem, status_geral) VALUES (?, ?, 'ENVIADO_PROTOCOLO') RETURNING id");
                $stmtDE->execute([$_SESSION['user_id'], $origem]);
                $de_id = $stmtDE->fetchColumn();

                // 2. Insere os itens granulares
                $stmtItem = $db->prepare("INSERT INTO itens_de (de_id, nome_documento, nf, ns_pa, cnpj, valor, status_item) VALUES (?, ?, ?, ?, ?, ?, 'ENVIADO_PROTOCOLO')");
                
                for ($i = 0; $i < count($nf_list); $i++) {
                    $nf = trim($nf_list[$i]);
                    if (!empty($nf)) {
                        $ns = trim($ns_list[$i] ?? '');
                        $cnpj = trim($cnpj_list[$i] ?? '');
                        // Converte valor padrão BR (vírgula) para Banco de Dados (ponto)
                        $valor = str_replace(['R$', '.', ' '], '', $valor_list[$i] ?? '0');
                        $valor = str_replace(',', '.', $valor);

                        // nome_documento será uma composição amigável para a tabela
                        $nome_doc = "Nota Fiscal: " . $nf; 
                        
                        $stmtItem->execute([$de_id, $nome_doc, $nf, $ns, $cnpj, $valor]);
                    }
                }

                $stmtAudit = $db->prepare("INSERT INTO auditoria (de_id, usuario_nome, perfil, acao, justificativa) VALUES (?, ?, ?, 'ENVIO_PROTOCOLO', 'Lote enviado para triagem do Protocolo')");
                $stmtAudit->execute([$de_id, $_SESSION['name'], $_SESSION['role']]);

                $db->commit();
                header("Location: /omap/painel?sucesso=1");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                die("Erro ao criar DE: " . $e->getMessage());
            }
        }
    }
}