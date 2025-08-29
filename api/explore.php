<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

// Random 20 posts
$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_picture_url
    FROM posts
    JOIN users ON posts.user_id = users.id
    ORDER BY RAND()
    LIMIT 20
");
$stmt->execute();
$posts = $stmt->fetchAll();

echo json_encode($posts);
