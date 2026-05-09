<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;

class OperadorController { 
      
    public function fila() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $db = Database::getConnection(); 
        
        $fases = ['AGUARDANDO_RECEBIMENTO_EXEC_FIN', 'AGUARDANDO_INSERCAO_NP', 'AGUARDANDO_INSERCAO_LF', 'AGUARDANDO_ATENDIMENTO_FINANCEIRO', 'AGUARDANDO_INSERCAO_OP', 'AGUARDANDO_GERACAO_RAP', 'AGUARDANDO_INSERCAO_OB', 'AGUARDANDO_AVAL_CANCELAMENTO', 'REJEITADO_PELO_ASSINADOR']; 
        $in = str_repeat('?,', count($fases) - 1) . '?'; 
        
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id WHERE i.status_atual IN ($in) ORDER BY i.prioridade DESC, i.op_numero ASC NULLS LAST, l.criado_em ASC"; 
        $stmt = $db->prepare($sql); 
        $stmt->execute($fases); 
        $todos_itens = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        $itens_receber = []; $itens_np = []; $itens_lf = []; $itens_atendimento = []; $itens_op = []; $itens_rap = []; $itens_ob = []; $itens_cancelar = []; 
        
        foreach ($todos_itens as $item) { 
            if (str_contains($item['status_atual'], 'RECEBIMENTO_EXEC_FIN') || str_contains($item['status_atual'], 'REJEITADO_PELO_ASSINADOR')) $itens_receber[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_NP') $itens_np[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_LF') $itens_lf[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_ATENDIMENTO_FINANCEIRO') $itens_atendimento[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_OP') $itens_op[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_GERACAO_RAP') $itens_rap[] = $item; 
            if ($item['status_atual'] === 'AGUARDANDO_INSERCAO_OB') $itens_ob[] = $item;  
            if ($item['status_atual'] === 'AGUARDANDO_AVAL_CANCELAMENTO') $itens_cancelar[] = $item; 
        } 
        
        $aba_ativa = $_GET['tab'] ?? 'receber'; 
        require __DIR__ . '/../views/operador_fila.php'; 
    } 

    public function gerarRapLote() { 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
            $db = Database::getConnection(); 
            $itens = $_POST['itens_selecionados'] ?? []; 
            if (empty($itens)) { echo "<script>alert('Selecione notas!'); history.back();</script>"; exit(); } 

            $usuario = $_SESSION['username']; $perfil = $_SESSION['role']; $timestamp = date('d/m/Y H:i'); 
            $numero_rap = "RAP-" . date('Y') . "-" . strtoupper(substr(uniqid(), -4)); 

            try { 
                $db->beginTransaction(); 
                $stmtRap = $db->prepare("INSERT INTO de_raps (numero_rap, criado_por) VALUES (?, ?) RETURNING id"); 
                $stmtRap->execute([$numero_rap, $usuario]); $rap_id = $stmtRap->fetchColumn(); 

                foreach ($itens as $item_id) { 
                    $stmtCurrent = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); 
                    $stmtCurrent->execute([$item_id]); 
                    $fase_anterior = $stmtCurrent->fetchColumn(); 

                    if ($fase_anterior !== 'AGUARDANDO_GERACAO_RAP') { 
                        $db->rollBack(); 
                        die("<script>alert('Item #{$item_id} não está aguardando geração de RAP.'); history.back();</script>"); 
                    } 

                    // 🩹 TRANSPLANTE LEGADO: Ao gerar o RAP, o item deve ir para a fila do 
                    // Gestor Financeiro (AGU_ASS_GESTOR_FINANCEIRO), NÃO para AGUARDANDO_INSERCAO_OB.
                    // O fluxo correcto é: RAP gerado → Gestor assina → Chefe → Agente → Ordenador → Só depois OB.
                    // Bug anterior: o item voltava para a aba OB do Operador, saltando toda a cadeia de assinaturas.
                    $stmtUpd = $db->prepare("UPDATE de_itens SET status_atual = 'AGU_ASS_GESTOR_FINANCEIRO', observacao_atual = ?, rap_id = ? WHERE id = ?"); 
                    $stmtUpd->execute(["[{$timestamp} - {$perfil}]: GERAR_RAP - \"Lote {$numero_rap}\"", $rap_id, $item_id]); 

                    $stmtEvt = $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_anterior, fase_nova, justificativa) VALUES (?, ?, ?, 'GERAR_RAP', ?, 'AGU_ASS_GESTOR_FINANCEIRO', ?)"); 
                    $stmtEvt->execute([$item_id, $usuario, $perfil, $fase_anterior, "Agrupado no RAP {$numero_rap}"]); 
                } 
                $db->commit(); 
                // 🩹 TRANSPLANTE LEGADO: Abre automaticamente a janela de impressão do RAP
                echo "<script>window.open('/operador/imprimir_rap?id={$rap_id}', '_blank'); window.location.href='/operador/fila?tab=rap';</script>"; exit(); 
            } catch (Exception $e) { 
                $db->rollBack(); 
                die("Erro ao gerar RAP: " . $e->getMessage()); 
            } 
        } 
    } 

    public function monitoramento() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $db = Database::getConnection(); 
        
        // 🐛 FIX Bug #1: View espera $itens_ativos mas controller passava $todos_itens
        // Agora busca TODOS os itens (incluindo ARQUIVADO) para o botão "Ver Arquivados" funcionar
        $sql = "SELECT i.*, l.numero_geral, l.origem_tipo FROM de_itens i JOIN de_lotes l ON i.lote_id = l.id ORDER BY i.prioridade DESC, i.op_numero ASC NULLS LAST, l.criado_em DESC LIMIT 500"; 
        $stmt = $db->query($sql); 
        $itens_ativos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []; 
        
        // 🐛 FIX Bug #2: View espera $raps mas controller não passava
        $sqlRaps = "SELECT * FROM de_raps ORDER BY criado_em DESC LIMIT 50"; 
        $stmtRaps = $db->query($sqlRaps); 
        $raps = $stmtRaps ? $stmtRaps->fetchAll(PDO::FETCH_ASSOC) : []; 
        
        require __DIR__ . '/../views/operador_monitoramento.php'; 
    } 

    /**
     * 🖨️ Imprimir RAP — Busca dados e renderiza a view de impressão
     * 🐛 FIX: Antes a rota /imprimir_rap fazia require directo sem passar variáveis
     */
    public function imprimirRap() { 
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); } 
        $db = Database::getConnection(); 
        $rap_id = $_GET['id'] ?? 0;

        $stmt = $db->prepare("SELECT * FROM de_raps WHERE id = ?");
        $stmt->execute([$rap_id]);
        $rap = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rap) { die("RAP não encontrado."); }

        // 🐛 FIX Bug #5: Ordena explicitamente por OP (op_numero ASC)
        $stmt = $db->prepare("SELECT i.*, l.numero_geral, l.origem_tipo 
            FROM de_itens i 
            JOIN de_lotes l ON i.lote_id = l.id 
            WHERE i.rap_id = ? 
            ORDER BY i.op_numero ASC, i.id ASC");
        $stmt->execute([$rap_id]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/imprimir_rap.php'; 
    } 

    /**
     * ❌ Excluir/Cancelar RAP — Estorna os itens e remove o RAP
     */
    public function excluirRap() { 
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operador') { header("Location: /"); exit(); } 
        $db = Database::getConnection(); 
        $rap_id = $_GET['id'] ?? 0;
        $usuario = $_SESSION['username']; 
        $perfil = $_SESSION['role']; 
        $timestamp = date('d/m/Y H:i'); 

        try { 
            $db->beginTransaction(); 

            // Busca itens do RAP que AINDA NÃO foram assinados (podem voltar)
            $stmt = $db->prepare("SELECT id, status_atual FROM de_itens WHERE rap_id = ?"); 
            $stmt->execute([$rap_id]); 
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC); 

            foreach ($itens as $item) { 
                // Só reverte itens que ainda não avançaram para OB ou assinatura
                if (!in_array($item['status_atual'], ['ARQUIVADO', 'CANCELADO_PELA_ORIGEM'])) {
                    $novo_status = 'AGUARDANDO_GERACAO_RAP'; 
                    $obs = "[{$timestamp} - {$perfil}]: ESTORNAR_RAP - \"RAP cancelado. Item voltou para fila de geração.\""; 
                    $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ?, rap_id = NULL WHERE id = ?")->execute([$novo_status, $obs, $item['id']]); 
                    $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'ESTORNAR_RAP', ?, ?)")->execute([$item['id'], $usuario, $perfil, $novo_status, "RAP cancelado, item voltou para fila"]); 
                }
            } 

            // Remove o RAP
            $db->prepare("DELETE FROM de_raps WHERE id = ?")->execute([$rap_id]); 

            $db->commit(); 
            header("Location: /operador/monitoramento"); 
            exit(); 
        } catch (Exception $e) { 
            $db->rollBack(); 
            die("Erro ao excluir RAP: " . $e->getMessage()); 
        } 
    } 

    /**
     * 🎯 Motor de Ações do Operador — Processa todas as ações da fila
     * 🐛 FIX Bug #5: Adicionado caso 'estornar_reiniciar' que caía em "Ação desconhecida"
     */
    public function acao() { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: /operador/fila"); exit(); } 
        
        $tipo_acao = $_POST['tipo_acao'] ?? ''; 
        $itens = $_POST['itens_selecionados'] ?? []; 
        $valor_input = $_POST['valor_input'] ?? ''; 
        $tab_origem = $_POST['tab_origem'] ?? 'receber'; 
        $observacao = $_POST['observacao'] ?? ''; 
        
        $db = Database::getConnection(); 
        $usuario = $_SESSION['username']; 
        $perfil = $_SESSION['role']; 
        $timestamp = date('d/m/Y H:i'); 

        if (empty($itens) && $tipo_acao !== 'estornar_reiniciar') { 
            echo "<script>alert('Selecione pelo menos um item!'); history.back();</script>"; exit(); 
        } 

        try { 
            $db->beginTransaction(); 

            switch ($tipo_acao) { 
                case 'receber': 
                    foreach ($itens as $item_id) { 
                        $stmt = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); 
                        $stmt->execute([$item_id]); 
                        $status = $stmt->fetchColumn(); 
                        
                        if (str_contains($status, 'RECEBIMENTO_EXEC_FIN') || str_contains($status, 'REJEITADO_PELO_ASSINADOR')) { 
                            $novo_status = 'AGUARDANDO_INSERCAO_NP'; 
                            $obs = "[{$timestamp} - {$perfil}]: RECEBER - \"Documento recebido pela Execução Financeira.\""; 
                            $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $obs, $item_id]); 
                            $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'RECEBER', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "Recebido pela Execução Financeira"]); 
                        } 
                    } 
                    break; 

                case 'inserir_np': 
                    foreach ($itens as $item_id) { 
                        $np = trim($valor_input); 
                        if (empty($np)) { $db->rollBack(); die("<script>alert('Informe o número da NP!'); history.back();</script>"); } 
                        $novo_status = 'AGUARDANDO_INSERCAO_LF'; 
                        $obs = "[{$timestamp} - {$perfil}]: INSERIR_NP - \"NP {$np} inserida.\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, np_numero = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $np, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'INSERIR_NP', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "NP {$np} inserida"]); 
                    } 
                    break; 

                case 'inserir_lf': 
                    foreach ($itens as $item_id) { 
                        $lf = trim($valor_input); 
                        if (empty($lf)) { $db->rollBack(); die("<script>alert('Informe o número da LF!'); history.back();</script>"); } 
                        $novo_status = 'AGUARDANDO_ATENDIMENTO_FINANCEIRO'; 
                        $obs = "[{$timestamp} - {$perfil}]: INSERIR_LF - \"LF {$lf} inserida.\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, lf_numero = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $lf, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'INSERIR_LF', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "LF {$lf} inserida"]); 
                    } 
                    break; 

                case 'atender_fin': 
                    foreach ($itens as $item_id) { 
                        $novo_status = 'AGUARDANDO_INSERCAO_OP'; 
                        $obs = "[{$timestamp} - {$perfil}]: ATENDER_FIN - \"Atendimento financeiro concluído.\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'ATENDER_FIN', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "Atendimento financeiro concluído"]); 
                    } 
                    break; 

                case 'inserir_op': 
                    foreach ($itens as $item_id) { 
                        $op = trim($valor_input); 
                        if (empty($op)) { $db->rollBack(); die("<script>alert('Informe o número da OP!'); history.back();</script>"); } 
                        $novo_status = 'AGUARDANDO_GERACAO_RAP'; 
                        $obs = "[{$timestamp} - {$perfil}]: INSERIR_OP - \"OP {$op} inserida.\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, op_numero = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $op, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'INSERIR_OP', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "OP {$op} inserida"]); 
                    } 
                    break; 

                case 'inserir_ob': 
                    foreach ($itens as $item_id) { 
                        $ob = trim($valor_input); 
                        if (empty($ob)) { $db->rollBack(); die("<script>alert('Informe o número da OB!'); history.back();</script>"); } 
                        // 🩹 TRANSPLANTE LEGADO: Após toda a cadeia de assinaturas, o Operador insere a OB
                        // e o processo é ARQUIVADO. Este é o passo FINAL do fluxo.
                        // Bug anterior: mandava de volta para AGU_ASS_GESTOR_FINANCEIRO, reiniciando o ciclo.
                        $novo_status = 'ARQUIVADO'; 
                        $obs = "[{$timestamp} - {$perfil}]: INSERIR_OB - \"OB {$ob} inserida. Processo arquivado com sucesso.\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, ob_numero = ?, observacao_atual = ?, data_pagamento = CURRENT_DATE WHERE id = ?")->execute([$novo_status, $ob, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'INSERIR_OB', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "OB {$ob} inserida. Processo arquivado."]); 
                    } 
                    break; 

                case 'autorizar_cancelamento': 
                    foreach ($itens as $item_id) { 
                        $novo_status = 'CANCELADO_PELA_ORIGEM'; 
                        $obs = "[{$timestamp} - {$perfil}]: CANCELAR - \"Cancelamento autorizado.{$observacao}\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ? WHERE id = ?")->execute([$novo_status, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'CANCELAR', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "Cancelamento autorizado: {$observacao}"]); 
                    } 
                    break; 

                // 🐛 FIX: Caso "gerar_rap" — a view operador_fila.php envia tipo_acao=gerar_rap
                // mas o switch não tinha este case, causando "Ação desconhecida: gerar_rap"
                // Redireciona para o método dedicado gerarRapLote() que já existe e faz todo o processamento.
                case 'gerar_rap':
                    $db->rollBack(); // Estorna a transação aberta — gerarRapLote() abre a sua própria
                    $this->gerarRapLote();
                    return; // gerarRapLote() faz redirect, não queremos cair no commit/redirect abaixo

                // 🐛 FIX Bug #5: Caso "Estornar e Reiniciar" que caía em "Ação desconhecida"
                case 'estornar_reiniciar':
                case 'estornar':
                    foreach ($itens as $item_id) { 
                        $stmt = $db->prepare("SELECT status_atual FROM de_itens WHERE id = ?"); 
                        $stmt->execute([$item_id]); 
                        $status_atual = $stmt->fetchColumn(); 
                        
                        // Retrocede o item para a fase de recebimento
                        $novo_status = 'AGUARDANDO_RECEBIMENTO_EXEC_FIN'; 
                        $obs = "[{$timestamp} - {$perfil}]: ESTORNAR_REINICIAR - \"Item estornado do status '{$status_atual}'. Motivo: {$observacao}\""; 
                        $db->prepare("UPDATE de_itens SET status_atual = ?, observacao_atual = ?, rap_id = NULL, op_numero = NULL, ob_numero = NULL WHERE id = ?")->execute([$novo_status, $obs, $item_id]); 
                        $db->prepare("INSERT INTO de_eventos (item_id, usuario_nip, perfil_atuante, acao, fase_nova, justificativa) VALUES (?, ?, ?, 'ESTORNAR_REINICIAR', ?, ?)")->execute([$item_id, $usuario, $perfil, $novo_status, "Estornado de '{$status_atual}' para reinício. Motivo: {$observacao}"]); 
                    } 
                    break; 

                default: 
                    $db->rollBack(); 
                    die("<script>alert('Ação desconhecida: " . htmlspecialchars($tipo_acao) . "'); history.back();</script>"); 
            } 

            $db->commit(); 
            header("Location: /operador/fila?tab=" . urlencode($tab_origem)); 
            exit(); 

        } catch (Exception $e) { 
            $db->rollBack(); 
            die("Erro ao processar ação: " . $e->getMessage()); 
        } 
    } 
}