<?php
session_start();
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;

// Get user profile info
$stmt = $pdo->prepare("SELECT id, username, full_name, bio, profile_picture_url FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    echo "User not found.";
    exit;
}

// Get follower count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = :id");
$stmt->execute(['id' => $profile_id]);
$follower_count = $stmt->fetchColumn();

// Get following count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :id");
$stmt->execute(['id' => $profile_id]);
$following_count = $stmt->fetchColumn();

// Get post count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :id");
$stmt->execute(['id' => $profile_id]);
$post_count = $stmt->fetchColumn();

// Check if current user is following this profile
$is_following = false;
if ($profile_id != $current_user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = :current AND following_id = :profile LIMIT 1");
    $stmt->execute(['current' => $current_user_id, 'profile' => $profile_id]);
    $is_following = (bool)$stmt->fetchColumn();
}

// Get user's posts
$stmt = $pdo->prepare("
    SELECT posts.*, COUNT(likes.id) as like_count
    FROM posts
    LEFT JOIN likes ON posts.id = likes.post_id
    WHERE posts.user_id = :user_id
    GROUP BY posts.id
    ORDER BY posts.created_at DESC
");
$stmt->execute(['user_id' => $profile_id]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['username']); ?> (@<?php echo htmlspecialchars($profile_user['username']); ?>) • Instagram</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">Instagram</a>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="explore.php">Explore</a>
                <a href="profile.php">Profile</a>
                <a href="messages.php">Messages</a>
                <a href="settings.php">Settings</a>
                <a href="auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="profile-container">
        <div class="profile-header">
            <div>
                <img src="<?php echo htmlspecialchars($profile_user['profile_picture_url'] ?? 'assets/uploads/default_avatar.png'); ?>" 
                     class="avatar-large" alt="<?php echo htmlspecialchars($profile_user['username']); ?>">
            </div>
            
            <div class="profile-info">
                <div class="profile-username">
                    <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>
                    
                    <?php if ($profile_id != $current_user_id): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <button type="submit" name="<?php echo $is_following ? 'unfollow' : 'follow'; ?>" 
                                    class="follow-button <?php echo $is_following ? 'unfollow-button' : ''; ?>"
                                    onclick="toggleFollow(<?php echo $profile_id; ?>, this)">
                                <?php echo $is_following ? 'Following' : 'Follow'; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="settings.php" style="background: #ffffff; color: #262626; border: 1px solid #dbdbdb; padding: 5px 9px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 14px;">
                            Edit profile
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <span><span class="count"><?php echo $post_count; ?></span> posts</span>
                    <span><span class="count"><?php echo $follower_count; ?></span> followers</span>
                    <span><span class="count"><?php echo $following_count; ?></span> following</span>
                </div>
                
                <div class="profile-bio">
                    <?php if ($profile_user['full_name']): ?>
                        <span class="full-name"><?php echo htmlspecialchars($profile_user['full_name']); ?></span>
                    <?php endif; ?>
                    <?php if ($profile_user['bio']): ?>
                        <span><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Posts Grid -->
        <div style="border-top: 1px solid #dbdbdb; margin-top: 44px; padding-top: 20px;">
            <?php if (empty($posts)): ?>
                <div style="text-align: center; padding: 60px 0;">
                    <div style="width: 62px; height: 62px; border: 2px solid #262626; border-radius: 50%; margin: 0 auto 22px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 24px;">📷</span>
                    </div>
                    <h2 style="font-size: 28px; font-weight: 300; margin-bottom: 10px;">No Posts Yet</h2>
                    <?php if ($profile_id == $current_user_id): ?>
                        <p style="color: #8e8e8e;">When you share photos, they will appear on your profile.</p>
                        <a href="index.php" style="color: #0095f6; text-decoration: none; font-weight: 600;">Share your first photo</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="explore-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="explore-post" onclick="openPost(<?php echo $post['id']; ?>)">
                            <?php if ($post['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post">
                            <?php else: ?>
                                <div style="background: #f8f8f8; display: flex; align-items: center; justify-content: center; height: 100%; color: #8e8e8e; font-size: 14px;">
                                    Text Post
                                </div>
                            <?php endif; ?>
                            <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                ♥ <?php echo $post['like_count']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function openPost(postId) {
            // In a real app, this would open a modal or navigate to post detail
            console.log('Opening post:', postId);
        }
        
        async function toggleFollow(userId, button) {
            const formData = new FormData();
            formData.append('profile_id', userId);
            
            try {
                const response = await fetch('api/follow.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.status === 'followed') {
                    button.textContent = 'Following';
                    button.classList.add('unfollow-button');
                } else if (data.status === 'unfollowed') {
                    button.textContent = 'Follow';
                    button.classList.remove('unfollow-button');
                }
            } catch (error) {
                console.error('Error toggling follow:', error);
            }
        }
    </script>
</body>
</html>