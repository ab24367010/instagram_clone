<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$sender_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['message_text'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message_text']);

    if ($message_text !== '') {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (:sender, :receiver, :text)");
        $stmt->execute(['sender' => $sender_id, 'receiver' => $receiver_id, 'text' => $message_text]);
        echo json_encode(['status' => 'sent']);
    } else {
        echo json_encode(['error' => 'Empty message']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
