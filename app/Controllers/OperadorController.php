<?php
namespace App\Controllers;

use App\core\Database;
use PDO;
use Exception;

class OperadorController {
    
    public function painel() {
        if ($_SESSION['role'] !== 'Operador') {
            die("Acesso negado. Área restrita à Execução Financeira.");
        }

        $db = Database::getConnection();
        
        // O Operador vê as DEs, EXCETO as que ainda estão retidas no Protocolo
        $stmt = $db->query("SELECT d.*, u.name as criador_nome, u.unit_omap 
                            FROM documentos_encaminhamento d 
                            JOIN users u ON d.criado_por = u.id 
                            WHERE d.status_geral != 'ENVIADO_PROTOCOLO'
                            ORDER BY d.criado_em ASC");
        $des = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Se a view ainda não existir, exibe um log para validarmos a chegada dos dados
        if (!file_exists(__DIR__ . '/../views/operador_painel.php')) {
            echo "<h2>Módulo Operador Carregado com Sucesso! (View em construção)</h2>";
            echo "<p>Aqui você verá os itens e terá o botão de Veto.</p>";
            echo "<pre>" . print_r($des, true) . "</pre>";
            exit();
        }

        require __DIR__ . '/../views/operador_painel.php';
    }

    // Ação Tática: O Veto Global (Rejeita apenas 1 item, a DE continua)
    public function aplicarVeto() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'Operador') {
            $item_id = $_POST['item_id'] ?? null;
            $motivo = $_POST['motivo_rejeicao'] ?? 'Sem justificativa informada.';

            if (!$item_id) die("Erro: Item não especificado.");

            try {
                $db = Database::getConnection();
                $db->beginTransaction();

                // 1. Busca qual é a DE deste item para o log
                $stmtCheck = $db->prepare("SELECT de_id FROM itens_de WHERE id = ?");
                $stmtCheck->execute([$item_id]);
                $de_id = $stmtCheck->fetchColumn();

                // 2. Altera O STATUS APENAS DESTE ITEM para DEVOLVIDO_OMAP
                $stmtUpdate = $db->prepare("UPDATE itens_de SET status_item = 'DEVOLVIDO_OMAP', motivo_rejeicao = ? WHERE id = ?");
                $stmtUpdate->execute([$motivo, $item_id]);

                // 3. Grava a ação intocável na Auditoria
                $stmtAudit = $db->prepare("INSERT INTO auditoria (de_id, item_id, usuario_nome, perfil, acao, justificativa) VALUES (?, ?, ?, ?, 'VETO_APLICADO', ?)");
                $stmtAudit->execute([$de_id, $item_id, $_SESSION['name'], $_SESSION['role'], $motivo]);

                $db->commit();
                
                // Retorna ao painel
                header("Location: /operador/painel?alerta=veto_aplicado");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                die("Erro ao aplicar veto: " . $e->getMessage());
            }
        }
    }
}