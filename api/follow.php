<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_id'])) {
    $profile_id = intval($_POST['profile_id']);

    if ($profile_id === $user_id) {
        echo json_encode(['error' => 'Cannot follow yourself']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = :user AND following_id = :profile");
    $stmt->execute(['user' => $user_id, 'profile' => $profile_id]);

    if ($stmt->fetch()) {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = :user AND following_id = :profile");
        $stmt->execute(['user' => $user_id, 'profile' => $profile_id]);
        echo json_encode(['status' => 'unfollowed']);
    } else {
        // Follow
        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (:user, :profile)");
        $stmt->execute(['user' => $user_id, 'profile' => $profile_id]);
        echo json_encode(['status' => 'followed']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
