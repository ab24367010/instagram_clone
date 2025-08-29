<?php
session_start();
require_once __DIR__ . '/includes/database.php';

// Login шалгалт
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Хэнгийн profile-г үзэж байгааг GET-ээр дамжуулж болно, эсвэл өөрийн profile
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;

// Хэрэглэгчийн мэдээлэл
$stmt = $pdo->prepare("SELECT id, username, full_name, bio, profile_picture_url FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    echo "User not found.";
    exit;
}

// Дагагч тоо
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = :id");
$stmt->execute(['id' => $profile_id]);
$follower_count = $stmt->fetchColumn();

// Дагаж байгаа тоо
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :id");
$stmt->execute(['id' => $profile_id]);
$following_count = $stmt->fetchColumn();

// Follow/unfollow check (current user-д зориулсан)
$is_following = false;
if ($profile_id != $current_user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = :current AND following_id = :profile LIMIT 1");
    $stmt->execute(['current' => $current_user_id, 'profile' => $profile_id]);
    $is_following = (bool)$stmt->fetchColumn();
}

// Profile user-ийн постууд
$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_picture_url
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.user_id = :user_id
    ORDER BY posts.created_at DESC
");
$stmt->execute(['user_id' => $profile_id]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($profile_user['username']); ?> - Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>Profile</h1>
    <nav>
        <a href="index.php">Feed</a> |
        <a href="settings.php">Settings</a> |
        <a href="auth/logout.php">Logout</a>
    </nav>
</header>

<main class="profile-container">
    <div class="profile-header">
        <img src="<?php echo $profile_user['profile_picture_url']; ?>" alt="avatar" class="avatar-large">
        <h2><?php echo htmlspecialchars($profile_user['username']); ?></h2>
        <p><?php echo htmlspecialchars($profile_user['full_name']); ?></p>
        <p><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
        <p>Followers: <?php echo $follower_count; ?> | Following: <?php echo $following_count; ?></p>

        <?php if ($profile_id != $current_user_id): ?>
            <form method="post" action="api/follow.php">
                <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                <button type="submit" name="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                    <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <section class="profile-posts">
        <h3>Posts</h3>
        <?php if (!$posts): ?>
            <p>Пост байхгүй байна.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-image">
                        <img src="<?php echo $post['image_url']; ?>" alt="post image">
                    </div>
                    <div class="post-caption">
                        <?php echo nl2br(htmlspecialchars($post['caption'])); ?>
                    </div>
                    <span class="timestamp"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
