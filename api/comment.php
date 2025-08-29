<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['comment_text'])) {
    $post_id = intval($_POST['post_id']);
    $comment_text = trim($_POST['comment_text']);

    if ($comment_text !== '') {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (:user, :post, :text)");
        $stmt->execute(['user' => $user_id, 'post' => $post_id, 'text' => $comment_text]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['error' => 'Empty comment']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
