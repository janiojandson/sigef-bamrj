<?php
namespace App\Controllers;

use App\Core\Database;
use PDO;

class UploadController {
    public function handleUpload() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['minutas'])) {
            $db = Database::getConnection();
            $protocol = $_POST['protocol'];
            $process_name = $_POST['process_name'];
            $cpf_cnpj = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
            $solemp = preg_replace('/\D/', '', $_POST['solemp'] ?? '');
            $priority = isset($_POST['priority']) ? 1 : 0;
            $observation = $_POST['observation'] ?? '';
            $username = $_SESSION['username'];
            $year = date('Y');

            // Criar diretório físico: public/uploads/2026/PROTOCOLO/
            $uploadDir = "uploads/$year/$protocol/";
            $fullPath = __DIR__ . "/../../public/" . $uploadDir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0777, true);
            }

            $db->beginTransaction();
            try {
                // 1. Inserir Documento
                $stmt = $db->prepare("INSERT INTO documents (protocol, name, cpf_cnpj, solemp, is_priority, current_observation, uploader_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $obs_entry = "[Início] $observation";
                $stmt->execute([$protocol, $process_name, $cpf_cnpj, $solemp, $priority, $obs_entry, $username, 'Caixa de Entrada - Enc. Finanças']);
                $docId = $db->lastInsertId();

                // 2. Processar Arquivos (Minutas e Anexos)
                $this->saveFiles($docId, $_FILES['minutas'], 'Minuta', $uploadDir, $fullPath);
                $this->saveFiles($docId, $_FILES['anexos'], 'Anexo', $uploadDir, $fullPath);

                $db->commit();
                header("Location: /index");
                exit();
            } catch (\Exception $e) {
                $db->rollBack();
                return "Erro no upload tático: " . $e->getMessage();
            }
        }
        return null;
    }

    private function saveFiles($docId, $fileArray, $type, $relPath, $fullPath) {
        $db = Database::getConnection();
        foreach ($fileArray['name'] as $key => $name) {
            if ($fileArray['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $fileArray['tmp_name'][$key];
                $safeName = basename($name);
                if (move_uploaded_file($tmpName, $fullPath . $safeName)) {
                    $stmt = $db->prepare("INSERT INTO document_files (document_id, filename, file_type) VALUES (?, ?, ?)");
                    $stmt->execute([$docId, $relPath . $safeName, $type]);
                }
            }
        }
    }
}