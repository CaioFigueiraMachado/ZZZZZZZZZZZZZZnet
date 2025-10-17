<?php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.username, u.avatar,
           (SELECT mensagem FROM mensagens 
            WHERE (remetente_id = ? AND destinatario_id = u.id) 
               OR (remetente_id = u.id AND destinatario_id = ?) 
            ORDER BY data_envio DESC LIMIT 1) as ultima_mensagem,
           (SELECT COUNT(*) FROM mensagens 
            WHERE destinatario_id = ? AND remetente_id = u.id AND lida = 0) as mensagens_nao_lidas
    FROM usuarios u
    WHERE u.id IN (
        SELECT remetente_id FROM mensagens WHERE destinatario_id = ?
        UNION
        SELECT destinatario_id FROM mensagens WHERE remetente_id = ?
    )
    ORDER BY (SELECT data_envio FROM mensagens 
              WHERE (remetente_id = ? AND destinatario_id = u.id) 
                 OR (remetente_id = u.id AND destinatario_id = ?) 
              ORDER BY data_envio DESC LIMIT 1) DESC

");

$stmt->execute([
    $_SESSION['usuario_id'], $_SESSION['usuario_id'],
    $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'],
    $_SESSION['usuario_id'], $_SESSION['usuario_id']
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['conversas' => $rows]);
