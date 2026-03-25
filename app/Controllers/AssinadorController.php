<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class AssinadorController {
    
    // 🛡️ Ação tática para Ativar/Desativar o Modo Substituto
    public function toggleSubstituto() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        $_SESSION['atuando_substituto'] = !($_SESSION['atuando_substituto'] ?? false);
        header("Location: /assinador/fila");
        exit();
    }

    // 🛡️ A Nova Fila Única (Foco na OP/Item, ignorando Lotes fechados)
    public function fila() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        $role = $_SESSION['role'];
        $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
        
        $fases_permissao = [];
        
        // Mapeamento de Hierarquia e Substituição
        if (in_array($role, ['Gestor_Financeiro', 'Gestor_Substituto'])) {
            $fases_permissao = $atuando_substituto ? ['AGU_ASS_GESTOR_FINANCEIRO', 'AGU_VRF_CHEINTE'] : ['AGU_ASS_GESTOR_FINANCEIRO'];
        } elseif ($role === 'Chefe_Departamento') {
            $fases_permissao = $atuando_substituto ? ['AGU_VRF_CHEINTE', 'AGU_VRF_VICE_DIRETOR'] : ['AGU_VRF_CHEINTE'];
        } elseif ($role === 'Agente_Fiscal') {
            $fases_permissao = $atuando_substituto ? ['AGU_VRF_VICE_DIRETOR', 'AGU_ASS_DIRETOR'] : ['AGU_VRF_VICE_DIRETOR'];
        } elseif ($role === 'Ordenador_Despesas') {
            $fases_permissao = ['AGU_ASS_DIRETOR'];
        }

        if (empty($fases_permissao)) die("Acesso não autorizado.");
        
        $db = Database::getConnection();
        
        $in = str_repeat('?,', count($fases_permissao) - 1) . '?';
        
        // Agora buscamos direto os itens, juntando o RAP para visualização da capa
        $sql = "SELECT i.*, r.numero_rap 
                FROM de_itens i 
                LEFT JOIN de_raps r ON i.rap_id = r.id 
                WHERE i.status_atual IN ($in) 
                ORDER BY i.prioridade DESC, i.id ASC";
                
        $stmtItens = $db->prepare($sql);
        $stmtItens->execute($fases_permissao);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/assinador_fila.php';
    }

    public function processarAcao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $itens = $_POST['itens_selecionados'] ?? [];
            $acao = $_POST['acao'] ?? ''; 
            $observacao = trim($_POST['observacao'] ?? '');
            
            if(empty($itens)) die("<script>alert('Selecione pelo menos um documento.'); history.back();</script>");

            $usuario = $_SESSION['username'];
            $role = $_SESSION['role'];
            $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
            $timestamp = date('d/m/Y H:i');

            try {
                $db->beginTransaction();

                foreach($itens as $item_id) {
                    $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                    $stmtCur->execute([$item_id]);
                    $fase_atual = $stmtCur->fetchColumn();

                    $obs_local = $observacao;

                    if ($acao === 'aprovar') {
                        $acao_log = 'ASSINATURA_APROVADA';
                        if(empty($obs_local)) $obs_local = "Documento verificado e assinado digitalmente.";

                        // Mapeamento Inteligente
                        if ($fase_atual === 'AGU_ASS_GESTOR_FINANCEIRO') $novo_status = 'AGU_VRF_CHEINTE';
                        elseif ($fase_atual === 'AGU_VRF_CHEINTE') {
                            $novo_status = ($role === 'Chefe_Departamento' && $atuando_substituto) ? 'AGU_ASS_DIRETOR' : 'AGU_VRF_VICE_DIRETOR';
                        }
                        elseif ($fase_atual === 'AGU_VRF_VICE_DIRETOR') {
                            $novo_status = ($role === 'Agente_Fiscal' && $atuando_substituto) ? 'AGUARDANDO_INSERCAO_OB' : 'AGU_ASS_DIRETOR';
                            if ($role === 'Chefe_Departamento' && $atuando_substituto) $novo_status = 'AGU_ASS_DIRETOR'; 
                        }
                        elseif ($fase_atual === 'AGU_ASS_DIRETOR') {
                            $novo_status = 'AGUARDANDO_INSERCAO_OB';
                        }
                        if ($atuando_substituto) $obs_local .= " (Assinado no Modo Substituto)";
                        
                    } elseif ($acao === 'rejeitar') {
                        if(empty($obs_local)) die("<script>alert('Justificativa obrigatória para rejeição!'); history.back();</script>");
                        $acao_log = 'REJEITADO_PELO_ASSINADOR';
                        
                        // Escada de Rejeição
                        if (in_array($role, ['Gestor_Financeiro', 'Gestor_Substituto'])) {
                            $novo_status = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN'; // Volta pro Operador
                        } else {
                            $novo_status = 'AGU_ASS_GESTOR_FINANCEIRO'; // Chefes devolvem pro Gestor
                        }
                    }

                    $obs_formatada = "[{$timestamp} - {$role}]: {$acao_log} - \"{$obs_local}\"";

                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $obs_formatada, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$item_id, $usuario, $role, $acao_log, $fase_atual, $novo_status, $obs_local]);
                }

                $db->commit();
                
                // Redireciona para a Fila Única
                header("Location: /assinador/fila"); exit();
                
            } catch (\Exception $e) { $db->rollBack(); die("Erro Tático: " . $e->getMessage()); }
        }
    }
}
