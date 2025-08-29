<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['q']) && strlen($_GET['q']) >= 2) {
    $search = "%" . $_GET['q'] . "%";
    
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, profile_picture_url
        FROM users
        WHERE (username LIKE :search1 OR full_name LIKE :search2)
          AND id != :user_id
        ORDER BY username ASC
        LIMIT 20
    ");
    $stmt->execute([
        'search1' => $search,
        'search2' => $search,
        'user_id' => $user_id
    ]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} else {
    echo json_encode([]);
}
