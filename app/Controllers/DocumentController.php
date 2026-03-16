<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class DocumentController {

    private function checkOperador() {
        if (($_SESSION['role'] ?? '') !== 'Operador') {
            http_response_code(403);
            die("Acesso Negado: Apenas Operadores podem manipular documentos de base.");
        }
    }

    public function uploadProcess() {
        $this->checkOperador();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $date_str = date('Ymd');
            $random_hash = strtoupper(bin2hex(random_bytes(3))); 
            $protocol = "BAMRJ-{$date_str}-{$random_hash}";
            
            $name = $_POST['process_name'] ?? '';
            $cpf_cnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $solemp = preg_replace('/\D/', '', $_POST['solemp'] ?? '');
            $is_priority = isset($_POST['priority']) ? 1 : 0;
            $obs = $_POST['observation'] ?? '';
            $uploader_name = $_SESSION['username'];
            $status = 'Caixa de Entrada - Enc. Finanças';
            $ano_atual = date('Y');
            
            $upload_dir = __DIR__ . "/../../public/uploads/{$ano_atual}/{$protocol}";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            try {
                $db->beginTransaction();
                $sql = "INSERT INTO documents (protocol, name, cpf_cnpj, solemp, status, is_priority, current_observation, uploader_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
                $stmt = $db->prepare($sql);
                $stmt->execute([$protocol, $name, $cpf_cnpj, $solemp, $status, $is_priority, "[Início] " . $obs, $uploader_name]);
                $doc_id = $stmt->fetchColumn();

                $this->processarArquivos('minutas', 'Minuta', $doc_id, $ano_atual, $protocol, $upload_dir, $db);
                $this->processarArquivos('anexos', 'Anexo', $doc_id, $ano_atual, $protocol, $upload_dir, $db);

                $db->commit();
                header("Location: /index"); exit();
            } catch (\Exception $e) {
                $db->rollBack(); die("Erro Crítico: " . $e->getMessage());
            }
        }
    }

    private function processarArquivos($inputName, $fileType, $docId, $ano, $protocol, $dir, $db) {
        if (!empty($_FILES[$inputName]['name'][0])) {
            $total = count($_FILES[$inputName]['name']);
            for ($i = 0; $i < $total; $i++) {
                $tmp_name = $_FILES[$inputName]['tmp_name'][$i];
                $name = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES[$inputName]['name'][$i]));
                if (move_uploaded_file($tmp_name, "{$dir}/{$name}")) {
                    $stmt = $db->prepare("INSERT INTO document_files (document_id, filename, file_type) VALUES (?, ?, ?)");
                    $stmt->execute([$docId, "{$ano}/{$protocol}/{$name}", $fileType]);
                }
            }
        }
    }

    public function cancelProcess() {
        $this->checkOperador();
        $db = Database::getConnection();
        $id = $_GET['id'] ?? 0;
        $obs = 'Processo cancelado pelo operador.';
        $timestamp = date('d/m/Y H:i');
        
        $stmt = $db->prepare("UPDATE documents SET status = 'Cancelado', current_observation = current_observation || '\n[' || ? || ' - Operador]: CANCELADO - ' || ? WHERE id = ?");
        $stmt->execute([$timestamp, $obs, $id]);
        
        $stmt = $db->prepare("INSERT INTO events (document_id, user_name, action, observation) VALUES (?, ?, 'CANCELAR', ?)");
        $stmt->execute([$id, $_SESSION['username'], $obs]);
        
        header("Location: /index"); exit();
    }

    public function uploadNE() {
        $this->checkOperador();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $id = $_GET['id'] ?? 0;
            $status_final = $_POST['final_status'] ?? 'Arquivado';

            $stmt = $db->prepare("SELECT protocol, created_at FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();

            if ($doc && !empty($_FILES['nota_empenho']['name'])) {
                $ano_doc = date('Y', strtotime($doc['created_at']));
                $upload_dir = __DIR__ . "/../../public/uploads/{$ano_doc}/{$doc['protocol']}";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $tmp_name = $_FILES['nota_empenho']['tmp_name'];
                $name = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['nota_empenho']['name']));

                if (move_uploaded_file($tmp_name, "{$upload_dir}/{$name}")) {
                    $db->beginTransaction();
                    $stmt = $db->prepare("UPDATE documents SET status = ? WHERE id = ?");
                    $stmt->execute([$status_final, $id]);
                    
                    $stmt = $db->prepare("INSERT INTO document_files (document_id, filename, file_type) VALUES (?, ?, 'Nota de Empenho')");
                    $stmt->execute([$id, "{$ano_doc}/{$doc['protocol']}/{$name}"]);

                    $stmt = $db->prepare("INSERT INTO events (document_id, user_name, action, observation) VALUES (?, ?, 'ANEXAR_NE', ?)");
                    $stmt->execute([$id, $_SESSION['username'], "Nota de Empenho ({$status_final}) anexada."]);
                    $db->commit();
                }
            }
            header("Location: /index"); exit();
        }
    }

    public function processAction() {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $doc_id = $_GET['id'] ?? 0;
            $action = $_POST['action'] ?? ($_GET['action'] ?? '');
            
            $obs = trim($_POST['new_observation'] ?? '');
            if (empty($obs)) {
                die("<div style='padding:20px; font-family:sans-serif; background:#dc3545; color:white;'><h1>⚠️ Erro Tático</h1><p>O despacho é OBRIGATÓRIO para aprovar ou devolver o processo. Volte e preencha o campo.</p><a href='javascript:history.back()' style='color:white;'>⬅️ Voltar</a></div>");
            }

            $username = $_SESSION['username'];
            $role = $_SESSION['role'];
            $is_sub = $_SESSION['is_substitute'] ?? false;

            $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
            $stmt->execute([$doc_id]);
            $doc = $stmt->fetch();
            if (!$doc) die("Documento não encontrado.");

            $status = $doc['status'];
            $current_obs = $doc['current_observation'];

            $acao_str = ($action === 'aprovar') ? 'APROVADO' : 'REJEITADO';
            $cargo = $is_sub ? "{$role} (SUBSTITUTO)" : ($role === 'Enc_Financas' ? 'Enc. Finanças' : $role);
            $timestamp = date('d/m/Y H:i');
            
            $current_obs .= "\n[{$timestamp} - {$cargo}]: {$acao_str} - \"{$obs}\"";

            $stmt = $db->prepare("INSERT INTO events (document_id, user_name, action, observation) VALUES (?, ?, ?, ?)");
            $stmt->execute([$doc_id, $username, strtoupper($action), $obs]);

            if ($action === 'rejeitar') {
                $status = 'Devolvido - Operador';
            } elseif ($action === 'aprovar') {
                if ($status === 'Caixa de Entrada - Enc. Finanças') $status = 'Caixa de Entrada - Chefe';
                elseif ($status === 'Caixa de Entrada - Chefe') $status = ($is_sub && $role === 'Chefe_Departamento') ? 'Caixa de Entrada - Diretor' : 'Caixa de Entrada - Vice-Diretor';
                elseif ($status === 'Caixa de Entrada - Vice-Diretor') $status = ($is_sub && $role === 'Vice_Diretor') ? 'Aguardando Empenho - Operador' : 'Caixa de Entrada - Diretor';
                elseif ($status === 'Caixa de Entrada - Diretor') $status = 'Aguardando Empenho - Operador';
            }

            $stmt = $db->prepare("UPDATE documents SET status = ?, current_observation = ? WHERE id = ?");
            $stmt->execute([$status, $current_obs, $doc_id]);

            header("Location: /index"); exit();
        }
    }

    public function getViewerData(): array {
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit(); }
        $doc_id = $_GET['id'] ?? 0;
        $db = Database::getConnection();
        $role = $_SESSION['role'] ?? '';
        
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$doc_id]);
        $doc = $stmt->fetch();
        if (!$doc) die("Documento não encontrado na Base de Dados.");

        if ($role === 'Usuário Comum') {
            $stmt = $db->prepare("SELECT * FROM document_files WHERE document_id = ? AND file_type = 'Nota de Empenho' ORDER BY id ASC");
        } else {
            $stmt = $db->prepare("SELECT * FROM document_files WHERE document_id = ? ORDER BY file_type DESC, id ASC");
        }
        
        $stmt->execute([$doc_id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['doc' => $doc, 'files' => is_array($files) ? $files : [], 'role' => $role];
    }

    public function getPdf() {
        if (!isset($_SESSION['user_id'])) { header("HTTP/1.1 403 Forbidden"); exit(); }
        $file = $_GET['file'] ?? '';
        $file = str_replace('..', '', $file); 
        $path = __DIR__ . '/../../public/uploads/' . ltrim($file, '/');

        $isDownload = isset($_GET['dl']) && $_GET['dl'] == '1';
        $disposition = $isDownload ? 'attachment' : 'inline';

        if (file_exists($path)) {
            while (ob_get_level()) { ob_end_clean(); }
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . $disposition . '; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($path);
            exit();
        } else {
            http_response_code(404);
            die("<div style='padding:20px; font-family:sans-serif; background:#dc3545; color:white;'><h1>⚠️ 404 - PDF não encontrado no Servidor</h1><p>Arquivo físico ausente: " . htmlspecialchars($file) . "</p></div>");
        }
    }

    // 🛡️ REQUISITO ATUALIZADO: Motor de Correção Total (Textos e Arquivados)
    public function editProcess() {
        $this->checkOperador();
        $db = Database::getConnection();
        $id = $_GET['id'] ?? 0;

        // Permite "ressuscitar" processos devolvidos, arquivados, cancelados ou anulados
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND status IN ('Devolvido - Operador', 'Arquivado', 'Cancelado', 'Anulado', 'Reforçado')");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            die("<div style='padding:20px; font-family:sans-serif; background:#dc3545; color:white;'><h1>⚠️ Acesso Negado</h1><p>Documento não encontrado ou não está disponível para correção.</p><a href='/' style='color:white;'>⬅️ Voltar ao Dashboard</a></div>");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obs = trim($_POST['observation'] ?? '');
            
            // Novos Dados Editados
            $name = trim($_POST['process_name'] ?? $doc['name']);
            $cpf_cnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? $doc['cpf_cnpj']);
            $solemp = preg_replace('/\D/', '', $_POST['solemp'] ?? $doc['solemp']);

            if (empty($obs) || empty($name)) {
                die("<div style='padding:20px; font-family:sans-serif; background:#dc3545; color:white;'><h1>⚠️ Erro Tático</h1><p>É OBRIGATÓRIO informar o Assunto e o que foi corrigido no campo de despacho.</p><a href='javascript:history.back()' style='color:white;'>⬅️ Voltar</a></div>");
            }

            $protocol = $doc['protocol'];
            $ano_doc = date('Y', strtotime($doc['created_at']));
            $upload_dir = __DIR__ . "/../../public/uploads/{$ano_doc}/{$protocol}";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            try {
                $db->beginTransaction();

                $this->processarArquivos('minutas', 'Minuta', $id, $ano_doc, $protocol, $upload_dir, $db);
                $this->processarArquivos('anexos', 'Anexo', $id, $ano_doc, $protocol, $upload_dir, $db);

                $timestamp = date('d/m/Y H:i');
                $current_obs = $doc['current_observation'] . "\n[{$timestamp} - Operador]: PROCESSO EDITADO/REINICIADO - \"{$obs}\"";
                $novo_status = 'Caixa de Entrada - Enc. Finanças'; // Volta para o início

                // Atualiza TUDO: Nome, CPF, SOLEMP e Status
                $stmt = $db->prepare("UPDATE documents SET name = ?, cpf_cnpj = ?, solemp = ?, status = ?, current_observation = ? WHERE id = ?");
                $stmt->execute([$name, $cpf_cnpj, $solemp, $novo_status, $current_obs, $id]);

                $stmt = $db->prepare("INSERT INTO events (document_id, user_name, action, observation) VALUES (?, ?, 'EDITAR', ?)");
                $stmt->execute([$id, $_SESSION['username'], $obs]);

                $db->commit();
                header("Location: /index");
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                die("Erro Crítico ao Salvar: " . $e->getMessage());
            }
        }

        $stmt = $db->prepare("SELECT * FROM document_files WHERE document_id = ? ORDER BY file_type DESC, id ASC");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/edit.php';
    }
}