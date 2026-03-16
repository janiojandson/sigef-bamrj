<?php
$uploadCtrl = new \App\Controllers\UploadController();
$error = $uploadCtrl->handleUpload();

// Gerar um protocolo sugerido (Ex: BAMRJ-20260312-ABCD)
$dateStr = date('Ymd');
$randomId = strtoupper(substr(uniqid(), -4));
$suggestedProtocol = "BAMRJ-{$dateStr}-{$randomId}";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Processo - BAMRJ</title>
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body>
    <header>
        <div><strong>BAMRJ</strong> | Inserir Novo Documento</div>
        <a href="/index" style="color: white; text-decoration: none;">Voltar</a>
    </header>

    <div class="container" style="max-width: 800px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        
        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Protocolo (Gerado Automaticamente):</label>
                <input type="text" name="protocol" value="<?php echo $suggestedProtocol; ?>" readonly>
            </div>

            <div class="form-group">
                <label>Nome do Processo / Favorecido:</label>
                <input type="text" name="process_name" required placeholder="Ex: Aquisição de Material de Escritório">
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>CPF / CNPJ:</label>
                    <input type="text" name="cpf_cnpj" placeholder="Apenas números">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>SOLEMP:</label>
                    <input type="text" name="solemp" placeholder="Número da SOLEMP">
                </div>
            </div>

            <div class="form-group">
                <label>Observação Inicial:</label>
                <textarea name="observation" rows="3" style="width: 100%; border-radius: 4px; border: 1px solid #ccc;"></textarea>
            </div>

            <div class="form-group" style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px dashed #00447c;">
                <label><strong>Minutas (PDF):</strong></label>
                <input type="file" name="minutas[]" multiple accept=".pdf" required>
                <br><br>
                <label><strong>Anexos (PDF/Imagens):</strong></label>
                <input type="file" name="anexos[]" multiple accept=".pdf,image/*">
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <input type="checkbox" name="priority" id="priority">
                <label for="priority" style="color: #e74c3c; font-weight: bold;"> Marcar como Prioridade Alta</label>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 20px; height: 50px; font-size: 1.1em;">
                LANÇAR PROCESSO NA REDE
            </button>
        </form>
    </div>
</body>
</html>