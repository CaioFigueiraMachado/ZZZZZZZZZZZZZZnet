<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = "Este email já está em uso!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $erro = "Este nome de usuário já está em uso!";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, username, senha) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$nome, $email, $username, $senha_hash])) {
                    $_SESSION['usuario_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    header('Location: feed.php');
                    exit();
                } else {
                    $erro = "Erro ao criar conta. Tente novamente.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-logo">
            <h1>ZZZZznet</h1>
        
        </div>
        
        <form method="POST" class="auth-form">
            <h2>Criar conta na ZZZZznet</h2>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-error"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="username">Nome de usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Criar conta</button>
            
            <div class="auth-links">
                <p>Já tem uma conta? <a href="login.php">Entrar</a></p>
            </div>
        </form>
    </div>
</body>
</html>