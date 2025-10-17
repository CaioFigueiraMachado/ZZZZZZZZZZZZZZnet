<?php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php';

$usuario_atual = getUsuarioById($pdo, $_SESSION['usuario_id']);

if (!isset($_GET['id'])) {
    header('Location: feed.php');
    exit();
}

$post_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, 
           (SELECT COUNT(*) FROM curtidas WHERE post_id = p.id) as curtidas_count,
           (SELECT COUNT(*) FROM comentarios WHERE post_id = p.id) as comentarios_count,
           EXISTS(SELECT 1 FROM curtidas WHERE post_id = p.id AND usuario_id = ?) as curtiu
    FROM posts p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$_SESSION['usuario_id'], $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: feed.php');
    exit();
}
if (isset($_GET['curtir'])) {
    $stmt = $pdo->prepare("SELECT id FROM curtidas WHERE usuario_id = ? AND post_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $post_id]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM curtidas WHERE usuario_id = ? AND post_id = ?");
        $stmt->execute([$_SESSION['usuario_id'], $post_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO curtidas (usuario_id, post_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $post_id]);
    }
    
    header('Location: post.php?id=' . $post_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_STRING);
    
    $stmt = $pdo->prepare("INSERT INTO comentarios (usuario_id, post_id, comentario) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $post_id, $comentario]);
    
    header('Location: post.php?id=' . $post_id);
    exit();
}

$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.avatar
    FROM comentarios c
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.data_comentario ASC
");
$stmt->execute([$post_id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="post-page">
        <a href="feed.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para o feed</a>
        
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
                <a href="post.php?id=<?php echo $post_id; ?>&curtir=true" class="post-action <?php echo $post['curtiu'] ? 'liked' : ''; ?>">
                    <i class="fas fa-heart"></i> <?php echo $post['curtidas_count']; ?>
                </a>
                <span class="post-action">
                    <i class="fas fa-comment"></i> <?php echo $post['comentarios_count']; ?>
                </span>
                <a href="#" class="post-action">
                    <i class="fas fa-share"></i>
                </a>
            </div>
        </div>

        <div class="comments-section">
            <h3>Comentários</h3>
            
            <form method="POST" class="comment-form">
                <div class="comment-input">
                    <img src="../uploads/avatars/<?php echo $usuario_atual['avatar']; ?>" alt="Avatar" class="comment-avatar">
                    <input type="text" name="comentario" placeholder="Escreva um comentário..." required>
                    <button type="submit" class="btn btn-primary">Comentar</button>
                </div>
            </form>
            
            <div class="comments">
                <?php if (count($comentarios) > 0): ?>
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="comment">
                            <img src="../uploads/avatars/<?php echo $comentario['avatar']; ?>" alt="Avatar" class="comment-avatar">
                            <div class="comment-content">
                                <div class="comment-header">
                                    <strong><?php echo $comentario['username']; ?></strong>
                                    <span><?php echo time_elapsed_string($comentario['data_comentario']); ?></span>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nenhum comentário ainda. Seja o primeiro a comentar!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>