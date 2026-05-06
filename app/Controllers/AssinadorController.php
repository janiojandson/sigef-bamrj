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
        
        // Persiste no BD
        $db->prepare("UPDATE users SET substituto_ativo = ? WHERE id = ?")
           ->execute([$novo_estado ? TRUE : FALSE, $_SESSION['user_id']]);
        
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
        
        // 🔧 CORREÇÃO: Ordenação RAP por OP em ordem CRESCENTE (facilita assinatura pelo oficial)
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

    public function processarAcao() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $itens = $_POST['itens_selecionados'] ?? [];
            $acao = $_POST['acao'] ?? ''; 
            $observacao = trim($_POST['observacao'] ?? '');
            
            if(empty($itens)) die("<script>alert('Selecione pelo menos um documento.'); history.back();</script>");

            $usuario = $_SESSION['username'];
            $role = $_SESSION['role'];
            
            // 🔄 Lê o estado do substituto do BD
            $stmt_sub = $db->prepare("SELECT substituto_ativo FROM users WHERE id = ?");
            $stmt_sub->execute([$_SESSION['user_id']]);
            $atuando_substituto = (bool)($stmt_sub->fetchColumn() ?? false);
            
            $timestamp = date('d/m/Y H:i');

            try {
                $db->beginTransaction();

                foreach($itens as $item_id) {
                    $stmtCur = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?");
                    $stmtCur->execute([$item_id]);
                    $fase_atual = $stmtCur->fetchColumn();
                    $obs_local = $observacao;

                    if ($acao === 'aprovar') {
                        $proxima_fase = $this->proximaFase($fase_atual, $atuando_substituto, $role);
                        if ($proxima_fase) {
                            $prefixo = $atuando_substituto ? '[SUBSTITUTO] ' : '';
                            $obs_formatada = "[" . $timestamp . " - {$role}]: {$prefixo}APROVADO - \"{$obs_local}\"";
                            $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                               ->execute([$proxima_fase, $obs_formatada, $item_id]);
                            $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'APROVAR', ?, ?, ?)")
                               ->execute([$item_id, $usuario, $role, $fase_atual, $proxima_fase, $obs_local]);
                        }
                    } elseif ($acao === 'rejeitar') {
                        if (empty($obs_local)) {
                            $db->rollBack();
                            die("<script>alert('Motivo obrigatório para rejeitar.'); history.back();</script>");
                        }
                        $fase_retorno = $this->faseRetornoRejeicao($fase_atual);
                        $prefixo = $atuando_substituto ? '[SUBSTITUTO] ' : '';
                        $obs_formatada = "[" . $timestamp . " - {$role}]: {$prefixo}REJEITADO - \"{$obs_local}\"";
                        $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")
                           ->execute([$fase_retorno, $obs_formatada, $item_id]);
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'REJEITAR', ?, ?, ?)")
                           ->execute([$item_id, $usuario, $role, $fase_atual, $fase_retorno, $obs_local]);
                    }
                }
                $db->commit();
                header("Location: /assinador/fila");
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                die("Erro ao processar: " . $e->getMessage());
            }
        }
    }

    private function proximaFase($atual, $substituto, $role) {
        $mapa = [
            'AGU_ASS_GESTOR_FINANCEIRO' => 'AGU_VRF_CHEINTE',
            'AGU_VRF_CHEINTE' => 'AGU_VRF_VICE_DIRETOR',
            'AGU_VRF_VICE_DIRETOR' => 'AGU_ASS_DIRETOR',
            'AGU_ASS_DIRETOR' => 'ARQUIVADO',
        ];
        return $mapa[$atual] ?? null;
    }

    private function faseRetornoRejeicao($atual) {
        $mapa = [
            'AGU_ASS_GESTOR_FINANCEIRO' => 'AGUARDANDO_INSERCAO_OP',
            'AGU_VRF_CHEINTE' => 'AGUARDANDO_INSERCAO_OP',
            'AGU_VRF_VICE_DIRETOR' => 'AGUARDANDO_INSERCAO_OP',
            'AGU_ASS_DIRETOR' => 'AGUARDANDO_INSERCAO_OP',
        ];
        return $mapa[$atual] ?? 'AGUARDANDO_INSERCAO_OP';
    }
}