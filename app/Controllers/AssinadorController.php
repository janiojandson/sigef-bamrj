<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class AssinadorController {
    
    public function verLote() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        $role = $_SESSION['role'];
        $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
        
        $fases_permissao = [];
        if (in_array($role, ['Enc_Financas', 'Ajudante_Encarregado'])) $fases_permissao = ['AGU_ASS_GESTOR_FINANCEIRO'];
        elseif ($role === 'Chefe_Departamento') $fases_permissao = $atuando_substituto ? ['AGU_VRF_CHEINTE', 'AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'];
        elseif ($role === 'Vice_Diretor') $fases_permissao = $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR', 'AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'];
        elseif ($role === 'Diretor') $fases_permissao = ['AGU_ASS_DIRETOR'];

        if (empty($fases_permissao)) die("Acesso não autorizado.");
        
        $id = $_GET['id'] ?? 0;
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM de_lotes WHERE id = ?");
        $stmt->execute([$id]); $lote = $stmt->fetch();
        if (!$lote) die("Lote não encontrado.");

        $in = str_repeat('?,', count($fases_permissao) - 1) . '?';
        $params = array_merge([$id], $fases_permissao);
        $stmtItens = $db->prepare("SELECT * FROM de_itens WHERE lote_id = ? AND status_atual IN ($in) ORDER BY prioridade DESC, id ASC");
        $stmtItens->execute($params);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/assinador_ver_lote.php';
    }

    public function processarAcao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $item_id = $_POST['item_id'] ?? 0;
            $lote_id = $_POST['lote_id'] ?? 0;
            $acao = $_POST['acao'] ?? ''; 
            $observacao = trim($_POST['observacao'] ?? '');
            
            $usuario = $_SESSION['username'];
            $role = $_SESSION['role'];
            $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
            $timestamp = date('d/m/Y H:i');

            // Descobre o status atual REAL do item
            $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
            $stmtCur->execute([$item_id]);
            $fase_atual = $stmtCur->fetchColumn();

            if ($acao === 'aprovar') {
                $acao_log = 'ASSINATURA_APROVADA';
                if(empty($observacao)) $observacao = "Documento verificado e assinado digitalmente.";

                // Mapeamento Inteligente: Qual o próximo destino?
                if ($fase_atual === 'AGU_ASS_GESTOR_FINANCEIRO') $novo_status = 'AGU_VRF_CHEINTE';
                elseif ($fase_atual === 'AGU_VRF_CHEINTE') {
                    $novo_status = ($role === 'Chefe_Departamento' && $atuando_substituto) ? 'AGU_ASS_DIRETOR' : 'AGU_VRF_VICE_DIRETOR';
                }
                elseif ($fase_atual === 'AGU_VRF_VICE_DIRETOR') {
                    $novo_status = ($role === 'Vice_Diretor' && $atuando_substituto) ? 'AGUARDANDO_INSERCAO_OB' : 'AGU_ASS_DIRETOR';
                    // Se o Chefe substitui Vice
                    if ($role === 'Chefe_Departamento' && $atuando_substituto) $novo_status = 'AGU_ASS_DIRETOR'; 
                }
                elseif ($fase_atual === 'AGU_ASS_DIRETOR') {
                    $novo_status = 'AGUARDANDO_INSERCAO_OB';
                }
                if ($atuando_substituto) $observacao .= " (Assinado no Modo Substituto)";
                
            } elseif ($acao === 'rejeitar') {
                $novo_status = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN';
                $acao_log = 'REJEITADO_PELO_ASSINADOR';
                if(empty($observacao)) die("<script>alert('Justificativa obrigatória!'); history.back();</script>");
            }

            $obs_formatada = "[{$timestamp} - {$role}]: {$acao_log} - \"{$observacao}\"";

            try {
                $db->beginTransaction();
                $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $obs_formatada, $item_id]);
                $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$item_id, $usuario, $role, $acao_log, $fase_atual, $novo_status, $observacao]);
                $db->commit();
                header("Location: /assinador/lote?id=" . $lote_id); exit();
            } catch (\Exception $e) { $db->rollBack(); die("Erro Tático: " . $e->getMessage()); }
        }
    }
}