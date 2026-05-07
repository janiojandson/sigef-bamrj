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
        $perfil = $_SESSION['role'];
        $timestamp = date('d/m/Y H:i');
        
        try {
            $db->beginTransaction();
            
            foreach ($itens as $item_id) {
                $stmtCurrent = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                $stmtCurrent->execute([$item_id]);
                $status_atual = $stmtCurrent->fetchColumn();
                
                if ($acao === 'aprovar') {
                    // Avança para a próxima fase da cadeia de assinaturas
                    $proxima_fase = $this->proximaFase($status_atual);
                    $obs = "[{$timestamp} - {$perfil}]: APROVAR - \"Aprovado.{$observacao}\"";
                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                       ->execute([$proxima_fase, $obs, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'APROVAR', ?, ?)")
                       ->execute([$item_id, $usuario, $perfil, $proxima_fase, "Aprovado. {$observacao}"]);
                       
                } elseif ($acao === 'rejeitar') {
                    if (empty($observacao)) {
                        $db->rollBack();
                        echo "<script>alert('Motivo da rejeição é obrigatório!'); history.back();</script>";
                        exit();
                    }
                    $novo_status = 'REJEITADO_PELO_ASSINADOR';
                    $obs = "[{$timestamp} - {$perfil}]: REJEITAR - \"Rejeitado: {$observacao}\"";
                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                       ->execute([$novo_status, $obs, $item_id]);
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'REJEITAR', ?, ?)")
                       ->execute([$item_id, $usuario, $perfil, $novo_status, "Rejeitado: {$observacao}"]);
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
    
    /**
     * 📋 Mapeamento da cadeia de aprovações
     */
    private function proximaFase($fase_atual) {
        $cadeia = [
            'AGU_ASS_GESTOR_FINANCEIRO' => 'AGU_VRF_CHEINTE',
            'AGU_VRF_CHEINTE' => 'AGU_VRF_VICE_DIRETOR',
            'AGU_VRF_VICE_DIRETOR' => 'AGU_ASS_DIRETOR',
            'AGU_ASS_DIRETOR' => 'CONCLUIDO',
        ];
        return $cadeia[$fase_atual] ?? $fase_atual;
    }
}