<?php
function getUsuarioById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPosts($pdo, $usuario_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.avatar, 
               (SELECT COUNT(*) FROM curtidas WHERE post_id = p.id) as curtidas_count,
               (SELECT COUNT(*) FROM comentarios WHERE post_id = p.id) as comentarios_count,
               EXISTS(SELECT 1 FROM curtidas WHERE post_id = p.id AND usuario_id = ?) as curtiu
        FROM posts p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.usuario_id = ? OR p.usuario_id IN (
            SELECT seguido_id FROM seguidores WHERE seguidor_id = ?
        )
        ORDER BY p.data_postagem DESC
    ");
    $stmt->execute([$usuario_id, $usuario_id, $usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'ano',
        'm' => 'mês',
        'w' => 'semana',
        'd' => 'dia',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' atrás' : 'agora mesmo';
}
?>