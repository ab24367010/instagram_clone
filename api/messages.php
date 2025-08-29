<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$current_user = $_SESSION['user_id'];

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid user_id']);
    exit;
}

$other_user = (int)$_GET['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message_text, created_at
        FROM messages
        WHERE (sender_id = :current1 AND receiver_id = :other1)
           OR (sender_id = :other2 AND receiver_id = :current2)
        ORDER BY created_at ASC
    ");
    
    $stmt->execute([
        ':current1' => $current_user,
        ':other1'   => $other_user,
        ':other2'   => $other_user,
        ':current2' => $current_user
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages ?? []);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Server error',
        'details' => $e->getMessage()
    ]);
}
