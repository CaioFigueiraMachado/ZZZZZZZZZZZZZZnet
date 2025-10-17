<?php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php'; 

$termo = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) : '';

$usuarios = [];
$posts = [];

if (!empty($termo)) {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               EXISTS(SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = u.id) as segue
        FROM usuarios u 
        WHERE u.nome LIKE ? OR u.username LIKE ?
        LIMIT 10
    ");
    $like_termo = '%' . $termo . '%';
    $stmt->execute([$_SESSION['usuario_id'], $like_termo, $like_termo]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.avatar, 
               (SELECT COUNT(*) FROM curtidas WHERE post_id = p.id) as curtidas_count,
               (SELECT COUNT(*) FROM comentarios WHERE post_id = p.id) as comentarios_count,
               EXISTS(SELECT 1 FROM curtidas WHERE post_id = p.id AND usuario_id = ?) as curtiu
        FROM posts p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.conteudo LIKE ?
        ORDER BY p.data_postagem DESC
        LIMIT 20
    ");
    $stmt->execute([$_SESSION['usuario_id'], $like_termo]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="search-page">
        <h1>Buscar</h1>
        
        <form method="GET" class="search-form">
            <div class="search-input">
                <input type="text" name="q" placeholder="Buscar usuários ou posts..." value="<?php echo htmlspecialchars($termo); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        
        <?php if (!empty($termo)): ?>
            <div class="search-results">
                <?php if (count($usuarios) > 0): ?>
                    <div class="search-section">
                        <h2>Usuários</h2>
                        <div class="users-list">
                            <?php foreach ($usuarios as $usuario): ?>
                                <div class="user-result">
                                    <img src="../uploads/avatars/<?php echo $usuario['avatar']; ?>" alt="Avatar" class="user-avatar">
                                    <div class="user-info">
                                        <h3><?php echo $usuario['nome']; ?></h3>
                                        <p>@<?php echo $usuario['username']; ?></p>
                                    </div>
                                    <div class="user-actions">
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <a href="profile.php?id=<?php echo $usuario['id']; ?>&seguir=true" class="btn <?php echo $usuario['segue'] ? 'btn-outline' : 'btn-primary'; ?>">
                                                <?php echo $usuario['segue'] ? 'Seguindo' : 'Seguir'; ?>
                                            </a>
                                        <?php endif; ?>
                                        <a href="profile.php?id=<?php echo $usuario['id']; ?>" class="btn btn-outline">Ver perfil</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (count($posts) > 0): ?>
                    <div class="search-section">
                        <h2>Posts</h2>
                        <div class="posts">
                            <?php foreach ($posts as $post): ?>
                                <div class="post">
                                    <div class="post-header">
                                        <img src="../uploads/avatars/<?php echo $post['avatar']; ?>" alt="Avatar" class="post-avatar">
                                        <div>
                                            <div class="post-user"><?php echo $post['username']; ?></div>
                                            <div class="post-time"><?php echo time_elapsed_string($post['data_postagem']); ?></div>
                                        </div>
                                    </div>
                                    <div class="post-content">
                                        <?php echo nl2br(htmlspecialchars($post['conteudo'])); ?>
                                    </div>
                                    <?php if ($post['imagem']): ?>
                                        <img src="../uploads/posts/<?php echo $post['imagem']; ?>" alt="Post image" class="post-image">
                                    <?php endif; ?>
                                    <div class="post-actions">
                                        <a href="feed.php?curtir=<?php echo $post['id']; ?>" class="post-action <?php echo $post['curtiu'] ? 'liked' : ''; ?>">
                                            <i class="fas fa-heart"></i> <?php echo $post['curtidas_count']; ?>
                                        </a>
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="post-action">
                                            <i class="fas fa-comment"></i> <?php echo $post['comentarios_count']; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (count($usuarios) === 0 && count($posts) === 0): ?>
                    <div class="empty-state">
                        <p>Nenhum resultado encontrado para "<?php echo htmlspecialchars($termo); ?>"</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>