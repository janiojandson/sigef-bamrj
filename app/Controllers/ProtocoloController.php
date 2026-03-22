<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class ProtocoloController {
    
    // 🗂️ FILA DE TRABALHO DO PROTOCOLO
    public function fila() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Protocolo', 'Admin'])) { 
            header("Location: /"); exit(); 
        }
        
        $db = Database::getConnection();
        
        // Busca TODOS OS ITENS que estão na Fase 2 (Aguardando Protocolo)
        // Fazemos um JOIN com de_lotes para pegar o Número da DE e a Origem
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo, l.criado_por 
                FROM de_itens i 
                JOIN de_lotes l ON i.lote_id = l.id 
                WHERE i.status_atual = 'AGUARDANDO_RECEBIMENTO_PROTOCOLO' 
                ORDER BY i.prioridade DESC, l.criado_em ASC";
                
        $stmt = $db->query($sql);
        $itens_pendentes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        require __DIR__ . '/../views/protocolo_fila.php';
    }

    // ✅ RECEBER ITEM (Dá o "Aceite" Físico e Digital)
    public function receberItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $observacao = trim($_POST['observacao'] ?? 'Documentação física recebida e conferida.');
            
            $usuario = $_SESSION['username'];
            $perfil = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');
            
            $nova_fase = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN'; // Avança para a Execução Financeira
            $obs_formatada = "[{$timestamp} - {$perfil}]: Recebido - \"{$observacao}\"";

            try {
                $db->beginTransaction();

                // 1. Atualiza o Status do Item e a Observação
                $stmt = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?");
                $stmt->execute([$nova_fase, $obs_formatada, $item_id]);

                // 2. Registra o Evento Tático na Auditoria
                $stmtEvento = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) 
                                            VALUES (?, ?, ?, 'RECEBER_PROTOCOLO', 'AGUARDANDO_RECEBIMENTO_PROTOCOLO', ?, ?)");
                $stmtEvento->execute([$item_id, $usuario, $perfil, $nova_fase, $observacao]);

                $db->commit();
                header("Location: /protocolo/fila");
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                die("Erro ao receber item: " . $e->getMessage());
            }
        }
    }
}