<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);

    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = :user AND post_id = :post");
    $stmt->execute(['user' => $user_id, 'post' => $post_id]);
    if ($stmt->fetch()) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = :user AND post_id = :post");
        $stmt->execute(['user' => $user_id, 'post' => $post_id]);
        echo json_encode(['status' => 'unliked']);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (:user, :post)");
        $stmt->execute(['user' => $user_id, 'post' => $post_id]);
        echo json_encode(['status' => 'liked']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
