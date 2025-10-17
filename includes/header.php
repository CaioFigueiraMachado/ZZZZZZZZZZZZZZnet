<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Das redes ao Mundo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1><a style="color: #FFFFFF;" href="../pages/feed.php">ZZZZznet</a></h1>
            </div>
            <nav class="nav">
                <a href="../pages/feed.php"><i></i> <span>Feed</span></a>
                <a href="../pages/search.php"><i></i> <span>Explorar</span></a>
                <a href="../pages/chat.php"><i></i> <span>Mensagens</span></a>
                <a href="../pages/profile.php?id=<?php echo $_SESSION['usuario_id']; ?>"><i></i> <span>Perfil</span></a>
                <a href="../includes/logout.php"><i></i> <span>Sair</span></a>
            </nav>
        </div>
    </header>
    <main class="main">