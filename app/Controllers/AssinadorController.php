<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class AssinadorController {
    
    // Mapeamento tático de qual fase cada perfil assina
    private $fases_assinatura = [
        'Enc_Financas' => 'AGU_ASS_GESTOR_FINANCEIRO',
        'Ajudante_Encarregado' => 'AGU_ASS_GESTOR_FINANCEIRO',
        'Chefe_Departamento' => 'AGU_VRF_CHEINTE',
        'Vice_Diretor' => 'AGU_VRF_VICE_DIRETOR',
        'Diretor' => 'AGU_ASS_DIRETOR'
    ];

    // Mapeamento do próximo passo (O Destino)
    private $proxima_fase = [
        'AGU_ASS_GESTOR_FINANCEIRO' => 'AGU_VRF_CHEINTE',
        'AGU_VRF_CHEINTE' => 'AGU_VRF_VICE_DIRETOR',
        'AGU_VRF_VICE_DIRETOR' => 'AGU_ASS_DIRETOR',
        'AGU_ASS_DIRETOR' => 'AGUARDANDO_INSERCAO_OB' // Do Diretor, volta pro Operador liquidar a OB
    ];

    public function verLote() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        $role = $_SESSION['role'];
        if (!array_key_exists($role, $this->fases_assinatura)) die("Acesso não autorizado.");

        $fase_permissao = $this->fases_assinatura[$role];
        $id = $_GET['id'] ?? 0;
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM de_lotes WHERE id = ?");
        $stmt->execute([$id]);
        $lote = $stmt->fetch();
        if (!$lote) die("Lote não encontrado.");

        // Traz apenas os itens deste lote que estão NA FASE deste Assinador
        $stmtItens = $db->prepare("SELECT * FROM de_itens WHERE lote_id = ? AND status_atual = ? ORDER BY prioridade DESC, id ASC");
        $stmtItens->execute([$id, $fase_permissao]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/assinador_ver_lote.php';
    }

    public function processarAcao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $lote_id = $_POST['lote_id'] ?? 0;
            $acao = $_POST['acao'] ?? ''; // 'aprovar' ou 'rejeitar'
            $observacao = trim($_POST['observacao'] ?? '');
            
            $usuario = $_SESSION['username'];
            $role = $_SESSION['role'];
            $timestamp = date('d/m/Y H:i');

            $fase_atual = $this->fases_assinatura[$role];

            if ($acao === 'aprovar') {
                $novo_status = $this->proxima_fase[$fase_atual];
                $acao_log = 'ASSINATURA_APROVADA';
                if(empty($observacao)) $observacao = "Documento verificado e assinado digitalmente.";
            } elseif ($acao === 'rejeitar') {
                // 🛡️ Se rejeitado, volta direto para o Operador consertar
                $novo_status = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN';
                $acao_log = 'REJEITADO_PELO_ASSINADOR';
                if(empty($observacao)) die("<script>alert('Justificativa obrigatória!'); history.back();</script>");
            } else {
                die("Ação inválida.");
            }

            $obs_formatada = "[{$timestamp} - {$role}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                
                $stmtUp = $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?");
                $stmtUp->execute([$novo_status, $obs_formatada, $item_id]);

                $stmtEv = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtEv->execute([$item_id, $usuario, $role, $acao_log, $fase_atual, $novo_status, $observacao]);

                $db->commit();
                header("Location: /assinador/lote?id=" . $lote_id);
                exit();
            } catch (\Exception $e) {
                $db->rollBack(); die("Erro Tático: " . $e->getMessage());
            }
        }
    }
}