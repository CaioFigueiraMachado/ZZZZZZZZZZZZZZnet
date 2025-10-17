<?php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: feed.php');
    exit();
}

$perfil_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$usuario_perfil = getUsuarioById($pdo, $perfil_id);

if (!$usuario_perfil) {
    $_SESSION['erro'] = "Usuário não encontrado.";
    header('Location: feed.php');
    exit();
}

$eh_proprio_perfil = ($_SESSION['usuario_id'] == $perfil_id);

if (isset($_GET['seguir'])) {
    if (!$eh_proprio_perfil) {
        $stmt = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $stmt->execute([$_SESSION['usuario_id'], $perfil_id]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $perfil_id]);
            $_SESSION['sucesso'] = "Deixou de seguir @" . $usuario_perfil['username'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $perfil_id]);
            $_SESSION['sucesso'] = "Agora você segue @" . $usuario_perfil['username'];
        }
        
        header('Location: profile.php?id=' . $perfil_id);
        exit();
    }
}
$stmt = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
$stmt->execute([$_SESSION['usuario_id'], $perfil_id]);
$segue = $stmt->fetch() ? true : false;

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM seguidores WHERE seguido_id = ?");
$stmt->execute([$perfil_id]);
$seguidores = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM seguidores WHERE seguidor_id = ?");
$stmt->execute([$perfil_id]);
$seguindo = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, 
           (SELECT COUNT(*) FROM curtidas WHERE post_id = p.id) as curtidas_count,
           (SELECT COUNT(*) FROM comentarios WHERE post_id = p.id) as comentarios_count,
           EXISTS(SELECT 1 FROM curtidas WHERE post_id = p.id AND usuario_id = ?) as curtiu
    FROM posts p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.usuario_id = ?
    ORDER BY p.data_postagem DESC
");
$stmt->execute([$_SESSION['usuario_id'], $perfil_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$posts) {
    $posts = [];
}
?>

<?php include '../includes/header.php'; ?>

<style>
.profile-header {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.profile-cover {
    height: 150px;
    background: linear-gradient(135deg, #388e3c 0%, #94f643ff 100%);
}

.profile-info {
    display: flex;
    padding: 20px;
    align-items: flex-start;
    gap: 20px;
    position: relative;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-top: -60px;
    background: white;
}

.profile-details {
    flex: 1;
}

.profile-details h1 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 24px;
}

.profile-details p {
    margin: 0 0 10px 0;
    color: #777;
}

.profile-bio {
    color: #555 !important;
    line-height: 1.5;
    margin: 10px 0 15px 0 !important;
}

.profile-stats {
    display: flex;
    gap: 20px;
    margin-top: 15px;
}

.stat {
    text-align: center;
}

.stat strong {
    display: block;
    font-size: 18px;
    color: #333;
    font-weight: 600;
}

.stat span {
    font-size: 14px;
    color: #777;
}

.profile-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #4a90e2;
    color: white;
}

.btn-primary:hover {
    background-color: #3a80d2;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 1px solid #4a90e2;
    color: #4a90e2;
}

.btn-outline:hover {
    background: #4a90e2;
    color: white;
    transform: translateY(-2px);
}

.profile-content {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.profile-content h2 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 22px;
    border-bottom: 2px solid #f0f2f5;
    padding-bottom: 10px;
}

.posts {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.post {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
    transition: transform 0.2s ease;
    border: 1px solid #e9ecef;
}

.post:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.post-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.post-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 8px;
}

.post-user {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.post-time {
    color: #777;
    font-size: 12px;
}

.post-content {
    margin-bottom: 8px;
    line-height: 1.4;
    color: #333;
    font-size: 14px;
}

.post-image {
    width: 100%;
    border-radius: 6px;
    margin-bottom: 8px;
    max-height: 300px;
    object-fit: cover;
}

.post-actions {
    display: flex;
    gap: 12px;
    border-top: 1px solid #eee;
    padding-top: 8px;
    margin-top: 8px;
}

.post-action {
    color: #777;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color 0.2s ease;
    font-size: 13px;
}

.post-action:hover {
    color: #4a90e2;
}

.post-action.liked {
    color: #e0245e;
}

.post-action.liked:hover {
    color: #c01a4f;
}

.empty-state {
    text-align: center;
    padding: 30px 20px;
    color: #777;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

@media (min-width: 768px) {
    .posts {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }
    
    .post {
        margin-bottom: 0;
    }
}

@media (max-width: 768px) {
    .profile-info {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    
    .profile-avatar-large {
        margin: -60px auto 15px auto;
    }
    
    .profile-stats {
        justify-content: center;
    }
    
    .profile-actions {
        justify-content: center;
        width: 100%;
    }
    
    .profile-actions .btn {
        flex: 1;
        max-width: 150px;
    }
    
    .post {
        padding: 10px;
    }
    
    .post-actions {
        gap: 10px;
    }
    
    .posts {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .profile-cover {
        height: 120px;
    }
    
    .profile-avatar-large {
        width: 100px;
        height: 100px;
        margin-top: -50px;
    }
    
    .profile-stats {
        gap: 15px;
    }
    
    .stat strong {
        font-size: 16px;
    }
    
    .stat span {
        font-size: 12px;
    }
    
    .btn {
        padding: 8px 15px;
        font-size: 13px;
    }
    
    .profile-content {
        padding: 15px;
    }
    
    .profile-content h2 {
        font-size: 20px;
    }
    
    .post-content {
        font-size: 13px;
    }
    
    .post-avatar {
        width: 28px;
        height: 28px;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.post {
    animation: fadeIn 0.3s ease-out;
}
.post-content {
    max-height: 3.6em;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.post:hover .post-content {
    max-height: none;
    -webkit-line-clamp: unset;
}

.alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="container">
    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['erro']; unset($_SESSION['erro']); ?>
        </div>
    <?php endif; ?>

    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info">
            <img src="../uploads/avatars/<?php echo htmlspecialchars($usuario_perfil['avatar']); ?>" alt="Avatar" class="profile-avatar-large">
            <div class="profile-details">
                <h1><?php echo htmlspecialchars($usuario_perfil['nome']); ?></h1>
                <p>@<?php echo htmlspecialchars($usuario_perfil['username']); ?></p>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($usuario_perfil['bio'])); ?></p>
                <div class="profile-stats">
                    <div class="stat">
                        <strong><?php echo count($posts); ?></strong>
                        <span>Posts</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $seguidores; ?></strong>
                        <span>Seguidores</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $seguindo; ?></strong>
                        <span>Seguindo</span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <?php if ($eh_proprio_perfil): ?>
                    <a href="edit-profile.php" class="btn btn-primary">Editar perfil</a>
                <?php else: ?>
                    <a href="profile.php?id=<?php echo $perfil_id; ?>&seguir=true" class="btn <?php echo $segue ? 'btn-outline' : 'btn-primary'; ?>">
                        <?php echo $segue ? 'Seguindo' : 'Seguir'; ?>
                    </a>
                    <a href="chat.php?user=<?php echo $perfil_id; ?>" class="btn btn-outline">Mensagem</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="profile-content">
        <h2>Posts</h2>
        <div class="posts">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-header">
                            <img src="../uploads/avatars/<?php echo htmlspecialchars($post['avatar']); ?>" alt="Avatar" class="post-avatar">
                            <div>
                                <div class="post-user"><?php echo htmlspecialchars($post['username']); ?></div>
                                <div class="post-time"><?php echo time_elapsed_string($post['data_postagem']); ?></div>
                            </div>
                        </div>
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['conteudo'])); ?>
                        </div>
                        <?php if ($post['imagem']): ?>
                            <img src="../uploads/posts/<?php echo htmlspecialchars($post['imagem']); ?>" alt="Post image" class="post-image">
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
            <?php else: ?>
                <div class="empty-state">
                    <p>Nenhum post ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>