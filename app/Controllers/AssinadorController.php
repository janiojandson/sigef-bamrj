<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class AssinadorController {
    
    /**
     * 🔄 Toggle do Modo Substituto — PERSISTENTE NO BANCO DE DADOS
     * O estado é gravado na coluna substituto_ativo da tabela users.
     * Sobrevive a reinicializações de navegador, PC ou sessão.
     */
    public function toggleSubstituto() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        
        $db = Database::getConnection();
        
        // Lê o estado atual do BD
        $stmt = $db->prepare("SELECT substituto_ativo FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $estado_atual = (bool)($stmt->fetchColumn() ?? false);
        $novo_estado = !$estado_atual;
        
        // Persiste no BD — 🐛 FIX: Usa 1/0 em vez de TRUE/FALSE para compatibilidade com PostgreSQL via PDO
        $db->prepare("UPDATE users SET substituto_ativo = ? WHERE id = ?")
           ->execute([$novo_estado ? 1 : 0, $_SESSION['user_id']]);
        
        // Sincroniza a sessão
        $_SESSION['atuando_substituto'] = $novo_estado;
        
        header("Location: /assinador/fila");
        exit();
    }

    public function fila() {
        if (!isset($_SESSION['user_id'])) { header("Location: /"); exit(); }
        
        // 🔄 Lê o estado do substituto do BD (fonte de verdade)
        $db = Database::getConnection();
        $stmt_sub = $db->prepare("SELECT substituto_ativo FROM users WHERE id = ?");
        $stmt_sub->execute([$_SESSION['user_id']]);
        $atuando_substituto = (bool)($stmt_sub->fetchColumn() ?? false);
        $_SESSION['atuando_substituto'] = $atuando_substituto;
        
        $role = $_SESSION['role'];
        $fases_permissao = [];
        
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
        
        $in = str_repeat('?,', count($fases_permissao) - 1) . '?';
        
        $sql = "SELECT i.*, r.numero_rap 
                FROM de_itens i 
                LEFT JOIN de_raps r ON i.rap_id = r.id 
                WHERE i.status_atual IN ($in) 
                ORDER BY r.numero_rap ASC, i.op_numero ASC NULLS LAST, i.prioridade DESC, i.id ASC";
                
        $stmtItens = $db->prepare($sql);
        $stmtItens->execute($fases_permissao);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../views/assinador_fila.php';
    }

    /**
     * ✍️ Motor de Ações do Assinador — Aprovar / Rejeitar
     * 🩹 TRANSPLANTE LEGADO: Lógica de aprovação com suporte a substituição hierárquica
     * e encaminhamento correcto após a assinatura do Ordenador (vai para OB, não CONCLUIDO).
     */
    public function acao() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /assinador/fila"); exit(); }
        
        $acao = $_POST['acao'] ?? '';
        $itens = $_POST['itens_selecionados'] ?? [];
        $observacao = trim($_POST['observacao'] ?? '');
        
        if (empty($itens)) {
            echo "<script>alert('Selecione pelo menos um item!'); history.back();</script>";
            exit();
        }
        
        $db = Database::getConnection();
        $usuario = $_SESSION['username'];
        $role = $_SESSION['role'];
        $atuando_substituto = $_SESSION['atuando_substituto'] ?? false;
        $timestamp = date('d/m/Y H:i');
        
        try {
            $db->beginTransaction();
            
            foreach ($itens as $item_id) {
                $stmtCurrent = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                $stmtCurrent->execute([$item_id]);
                $fase_atual = $stmtCurrent->fetchColumn();
                $obs_local = $observacao;
                
                if ($acao === 'aprovar') {
                    $acao_log = 'ASSINATURA_APROVADA';
                    if (empty($obs_local)) $obs_local = "Documento verificado e assinado digitalmente.";

                    // 🩹 TRANSPLANTE LEGADO: Lógica hierárquica completa com suporte a substituto
                    // Quando o Ordenador de Despesas assina, o processo vai para AGUARDANDO_INSERCAO_OB
                    // (o Operador insere a OB e arquiva), NÃO para CONCLUIDO.
                    if ($fase_atual === 'AGU_ASS_GESTOR_FINANCEIRO') {
                        $novo_status = 'AGU_VRF_CHEINTE';
                    } elseif ($fase_atual === 'AGU_VRF_CHEINTE') {
                        $novo_status = ($role === 'Chefe_Departamento' && $atuando_substituto) ? 'AGU_ASS_DIRETOR' : 'AGU_VRF_VICE_DIRETOR';
                    } elseif ($fase_atual === 'AGU_VRF_VICE_DIRETOR') {
                        $novo_status = ($role === 'Agente_Fiscal' && $atuando_substituto) ? 'AGUARDANDO_INSERCAO_OB' : 'AGU_ASS_DIRETOR';
                        if ($role === 'Chefe_Departamento' && $atuando_substituto) $novo_status = 'AGU_ASS_DIRETOR';
                    } elseif ($fase_atual === 'AGU_ASS_DIRETOR') {
                        // 🩹 CORREÇÃO CRÍTICA: Após assinatura do Ordenador, vai para inserção de OB
                        $novo_status = 'AGUARDANDO_INSERCAO_OB';
                    } else {
                        $novo_status = $fase_atual; // Segurança: não avança se fase desconhecida
                    }
                    
                    if ($atuando_substituto) $obs_local .= " (Assinado no Modo Substituto)";
                    
                    $obs_formatada = "[{$timestamp} - {$role}]: {$acao_log} - \"{$obs_local}\"";
                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                       ->execute([$novo_status, $obs_formatada, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")
                       ->execute([$item_id, $usuario, $role, $acao_log, $fase_atual, $novo_status, $obs_local]);
                       
                } elseif ($acao === 'rejeitar') {
                    if (empty($observacao)) {
                        $db->rollBack();
                        echo "<script>alert('Motivo da rejeição é obrigatório!'); history.back();</script>";
                        exit();
                    }
                    $acao_log = 'REJEITADO_PELO_ASSINADOR';
                    
                    // 🩹 TRANSPLANTE LEGADO: Lógica de rejeição hierárquica
                    // Gestor rejeita → volta para o Operador (REJEITADO_PELO_ASSINADOR)
                    // Oficial superior rejeita → volta para o Gestor (AGU_ASS_GESTOR_FINANCEIRO)
                    if (in_array($role, ['Gestor_Financeiro', 'Gestor_Substituto'])) {
                        $novo_status = 'REJEITADO_PELO_ASSINADOR';
                        $obs_local = "DEVOLVIDO PELO GESTOR FIN: " . $obs_local;
                    } else {
                        $novo_status = 'AGU_ASS_GESTOR_FINANCEIRO';
                        $obs_local = "DEVOLVIDO PELO OFICIAL SUPERIOR: " . $obs_local;
                    }
                    
                    $obs_formatada = "[{$timestamp} - {$role}]: {$acao_log} - \"{$obs_local}\"";
                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                       ->execute([$novo_status, $obs_formatada, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, ?, ?, ?, ?)")
                       ->execute([$item_id, $usuario, $role, $acao_log, $fase_atual, $novo_status, $obs_local]);
                }
            }
            
            $db->commit();
            header("Location: /assinador/fila");
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            die("Erro ao processar ação: " . $e->getMessage());
        }
    }
}