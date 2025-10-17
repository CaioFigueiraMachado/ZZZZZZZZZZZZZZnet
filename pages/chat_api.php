<?php
require_once '../includes/auth-check.php';
require_once '../includes/conexao.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $user_chat_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $mensagem = filter_input(INPUT_POST, 'mensagem', FILTER_UNSAFE_RAW);

    if (!$user_chat_id || !$mensagem) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $user_chat_id, $mensagem]);
    $insertId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT m.*, u.username, u.avatar FROM mensagens m JOIN usuarios u ON m.remetente_id = u.id WHERE m.id = ?");
    $stmt->execute([$insertId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['message' => $row]);
        exit();
    }

    http_response_code(500);
    echo json_encode(['error' => 'Não foi possível inserir a mensagem']);
    exit();
}

if ($method === 'GET') {
    $user_chat_id = filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $since_id = filter_input(INPUT_GET, 'since_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$user_chat_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        exit();
    }

    $query = "SELECT m.*, u.username, u.avatar FROM mensagens m JOIN usuarios u ON m.remetente_id = u.id
        WHERE ((m.remetente_id = ? AND m.destinatario_id = ?) OR (m.remetente_id = ? AND m.destinatario_id = ?))";

    $params = [$_SESSION['usuario_id'], $user_chat_id, $user_chat_id, $_SESSION['usuario_id']];
    if ($since_id) {
        $query .= " AND m.id > ?";
        $params[] = $since_id;
    }
    $query .= " ORDER BY m.id ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read where the current user is the destinatario
    $idsToMark = [];
    foreach ($rows as $r) {
        if (intval($r['destinatario_id']) === intval($_SESSION['usuario_id'])) {
            $idsToMark[] = intval($r['id']);
        }
    }

    if (!empty($idsToMark)) {
        // build placeholders
        $placeholders = implode(',', array_fill(0, count($idsToMark), '?'));
        $updateSql = "UPDATE mensagens SET lida = 1 WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute($idsToMark);
    }

    echo json_encode(['messages' => $rows]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);
