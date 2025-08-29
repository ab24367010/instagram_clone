<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$other_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($other_id) {
    $stmt = $pdo->prepare("
        SELECT messages.*, u1.username as sender_name, u2.username as receiver_name
        FROM messages
        JOIN users u1 ON messages.sender_id = u1.id
        JOIN users u2 ON messages.receiver_id = u2.id
        WHERE (sender_id = :user AND receiver_id = :other)
           OR (sender_id = :other AND receiver_id = :user)
        ORDER BY created_at ASC
    ");
    $stmt->execute(['user' => $user_id, 'other' => $other_id]);
    $messages = $stmt->fetchAll();
    echo json_encode($messages);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user_id']);
}
