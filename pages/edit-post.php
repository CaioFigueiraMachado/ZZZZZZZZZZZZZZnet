<?php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: feed.php');
    exit();
}

$post_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND usuario_id = ?");
$stmt->execute([$post_id, $_SESSION['usuario_id']]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: feed.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conteudo = filter_input(INPUT_POST, 'conteudo', FILTER_SANITIZE_STRING);
    $imagem = $post['imagem'];

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $nome_imagem = uniqid() . '.' . $extensao;
        $destino = '../uploads/posts/' . $nome_imagem;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
            if ($imagem) {
                @unlink('../uploads/posts/' . $imagem);
            }
            $imagem = $nome_imagem;
        }
    }

    $stmt = $pdo->prepare("UPDATE posts SET conteudo = ?, imagem = ? WHERE id = ?");
    $stmt->execute([$conteudo, $imagem, $post_id]);

    header('Location: profile.php?id=' . $_SESSION['usuario_id'] . '&sucesso=editado');
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>Editar Post</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="conteudo">Conteúdo</label>
            <textarea name="conteudo" id="conteudo" rows="4" required><?php echo htmlspecialchars($post['conteudo']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="imagem">Imagem (opcional)</label>
            <?php if ($post['imagem']): ?>
                <img src="../uploads/posts/<?php echo $post['imagem']; ?>" alt="Imagem atual" style="max-width:200px;display:block;margin-bottom:8px;">
            <?php endif; ?>
            <input type="file" name="imagem" id="imagem" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="post.php?id=<?php echo $post_id; ?>" class="btn btn-outline">Cancelar</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
