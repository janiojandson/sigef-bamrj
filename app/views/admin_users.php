<?php
$adminCtrl = new \App\Controllers\AdminController();
$adminCtrl->handleCreate();
$adminCtrl->handleEdit();
$users = $adminCtrl->listUsers();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Utilizadores - BAMRJ</title>
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body>
    <header>
        <div><strong>BAMRJ</strong> | Administração de Sistema</div>
        <a href="/index" style="color: white; text-decoration: none;">Voltar ao Dashboard</a>
    </header>

    <main class="container">
        <section style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3>Cadastrar Novo Militar</h3>
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Nome Completo / Guerra" required>
                <input type="text" name="username" placeholder="Login (Utilizador)" required>
                <input type="password" name="password" placeholder="Senha Inicial" required>
                <select name="role" required>
                    <option value="Operador">Operador</option>
                    <option value="Protocolo">Protocolo Base</option>
                    <option value="Ajud_Enc_Financas">Ajudante Enc. Finanças</option>
                    <option value="Enc_Financas">Encarregado de Finanças</option>
                    <option value="Chefe_Departamento">Chefe de Departamento</option>
                    <option value="Vice_Diretor">Vice-Diretor</option>
                    <option value="Diretor">Diretor</option>
                    <option value="Admin">Administrador</option>
                </select>
                <button type="submit" class="btn-primary">CADASTRAR</button>
            </form>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Utilizador</th>
                    <th>Perfil</th>
                    <th>Trava de Senha</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                    <td><?php echo $u['must_change_password'] ? '🔴 Obrigatória' : '🟢 OK'; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <select name="role" style="width: auto; padding: 5px;">
                                <option value="Operador" <?php echo $u['role'] == 'Operador' ? 'selected' : ''; ?>>Operador</option>
                                <option value="Protocolo" <?php echo $u['role'] == 'Protocolo' ? 'selected' : ''; ?>>Protocolo Base</option>
                                <option value="Ajud_Enc_Financas" <?php echo $u['role'] == 'Ajud_Enc_Financas' ? 'selected' : ''; ?>>Ajud. Enc. Finanças</option>
                                <option value="Enc_Financas" <?php echo $u['role'] == 'Enc_Financas' ? 'selected' : ''; ?>>Enc. Finanças</option>
                                <option value="Admin" <?php echo $u['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <input type="password" name="password" placeholder="Nova Senha (vazio p/ manter)" style="width: 150px; padding: 5px;">
                            <button type="submit" style="background: #2980b9; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Salvar</button>
                        </form>
                        <?php if($u['username'] !== 'admin'): ?>
                            <a href="/admin/delete?id=<?php echo $u['id']; ?>" onclick="return confirm('Confirmar exclusão?')" style="color: #c0392b; margin-left: 10px; text-decoration: none;">Excluir</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>