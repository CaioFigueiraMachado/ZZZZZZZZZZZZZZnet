<?php
// pages/edit-profile.php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php';

$usuario = getUsuarioById($pdo, $_SESSION['usuario_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['usuario_id']]);
    if ($stmt->fetch()) {
        $erro = "Este nome de usuário já está em uso!";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, username = ?, bio = ? WHERE id = ?");
        $stmt->execute([$nome, $username, $bio, $_SESSION['usuario_id']]);

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $nome_avatar = uniqid() . '.' . $extensao;
            $destino = '../uploads/avatars/' . $nome_avatar;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destino)) {
                if ($usuario['avatar'] !== 'default-avatar.jpg') {
                    unlink('../uploads/avatars/' . $usuario['avatar']);
                }
                
                $stmt = $pdo->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
                $stmt->execute([$nome_avatar, $_SESSION['usuario_id']]);
            }
        }

        if (!empty($senha_atual) && !empty($nova_senha)) {
            if (password_verify($senha_atual, $usuario['senha'])) {
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$nova_senha_hash, $_SESSION['usuario_id']]);
                $sucesso = "Perfil e senha atualizados com sucesso!";
            } else {
                $erro = "Senha atual incorreta!";
            }
        } else {
            $sucesso = "Perfil atualizado com sucesso!";
        }
        $_SESSION['username'] = $username;
    }
}

$usuario = getUsuarioById($pdo, $_SESSION['usuario_id']);
?>

<?php include '../includes/header.php'; ?>

<style>
.container {
    max-width: 500px;
    margin: 0 auto;
    padding: 32px 0;
  
}
.edit-profile {
    background: none;
    box-shadow: none;
    border-radius: 0;
    padding: 0;
}
.edit-profile h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #222;
    margin-bottom: 24px;
    text-align: center;
}
.alert {
    padding: 10px 18px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 1rem;
    text-align: center;
}
.alert-error {
    background: #ffeaea;
    color: #d32f2f;
    border: 1px solid #f5c6cb;
}
.alert-success {
    background: #eafaf1;
    color: #388e3c;
    border: 1px solid #c3e6cb;
}
.edit-profile-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.form-group label {
    font-size: 1rem;
    color: #444;
    font-weight: 500;
}
.form-group input,
.form-group textarea {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #eee;
    background: #fafafa;
    font-size: 1rem;
    color: #222;
    outline: none;
    box-shadow: none;
    transition: border 0.2s;
}
.form-group input:focus,
.form-group textarea:focus {
    border: 1px solid #bbb;
}
.avatar-upload {
    display: flex;
    align-items: center;
    gap: 16px;
}
.avatar-preview {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: none;
    box-shadow: none;
}
.edit-profile-form h3 {
    font-size: 1.1rem;
    color: #222;
    margin: 18px 0 8px 0;
    font-weight: 500;
}
.btn, .btn-primary, .btn-outline {
    padding: 8px 22px;
    border-radius: 20px;
    font-size: 1rem;
    border: none;
    background: #f5f5f5;
    color: #222;
    transition: background 0.2s;
    box-shadow: none;
    outline: none;
    cursor: pointer;
    margin-right: 8px;
    margin-top: 8px;
    text-decoration: none;
    display: inline-block;
}
.btn-primary {
    background: #222;
    color: #fff;
}
.btn-outline {
    background: #fff;
    color: #222;
    border: 1px solid #ddd;
}
.btn:hover, .btn-primary:hover, .btn-outline:hover {
    background: #eaeaea;
    color: #111;
}
</style>

<div class="container">
    <div class="edit-profile">
        <h1>Editar Perfil</h1>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-error"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="edit-profile-form">
            <div class="form-group">
                <label for="avatar">Foto de perfil</label>
                <div class="avatar-upload">
                    <img src="../uploads/avatars/<?php echo $usuario['avatar']; ?>" alt="Avatar" class="avatar-preview">
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                </div>
            </div>
            
            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="username">Nome de usuário</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usuario['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="bio">Biografia</label>
                <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($usuario['bio']); ?></textarea>
            </div>
            
            <h3>Alterar senha</h3>
            
            <div class="form-group">
                <label for="senha_atual">Senha atual</label>
                <input type="password" id="senha_atual" name="senha_atual">
            </div>
            
            <div class="form-group">
                <label for="nova_senha">Nova senha</label>
                <input type="password" id="nova_senha" name="nova_senha">
            </div>
            
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
            <a href="profile.php?id=<?php echo $_SESSION['usuario_id']; ?>" class="btn btn-outline">Cancelar</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>