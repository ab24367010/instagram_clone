<?php
// Debugging ON during dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/includes/database.php';

// Login check
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? '';

// =======================
// Feed (own + following)
// =======================
$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_picture_url
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.user_id = :uid1
       OR posts.user_id IN (
           SELECT following_id FROM followers WHERE follower_id = :uid2
       )
    ORDER BY posts.created_at DESC
");
$stmt->execute(['uid1' => $user_id, 'uid2' => $user_id]);
$posts = $stmt->fetchAll();

// =======================
// Handle search (follow someone)
// =======================
$search_results = [];
if (!empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt_search = $pdo->prepare("
        SELECT id, username, profile_picture_url
        FROM users
        WHERE username LIKE :search
          AND id != :uid1
          AND id NOT IN (
              SELECT following_id FROM followers WHERE follower_id = :uid2
          )
    ");
    $stmt_search->execute([
        'search' => $search,
        'uid1' => $user_id,
        'uid2' => $user_id
    ]);
    $search_results = $stmt_search->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feed - Instagram Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>Feed</h1>
    <nav>
        <a href="explore.php">Explore</a> |
        <a href="profile.php">Profile</a> |
        <a href="settings.php">Settings</a> |
        <a href="auth/logout.php">Logout</a>
    </nav>
</header>

<!-- Search users to follow -->
<section class="search-follow">
    <form method="get" action="index.php">
        <input type="text" name="search" placeholder="Search users to follow..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        <button type="submit">Search</button>
    </form>

    <?php if (!empty($search_results)): ?>
        <h3>Search results:</h3>
        <ul>
            <?php foreach ($search_results as $u): ?>
                <li>
                    <img src="<?php echo htmlspecialchars($u['profile_picture_url'] ?? 'assets/uploads/default_avatar.png'); ?>" class="avatar-small">
                    <?php echo htmlspecialchars($u['username'] ?? 'Unknown'); ?>
                    <form method="post" class="follow-form" data-user-id="<?php echo $u['id']; ?>">
                        <button type="submit">Follow</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="post">
    <div class="new-post">
    <form action="post.php" method="post" enctype="multipart/form-data">
        <label>Upload Image</label>
        <input type="file" name="image" required>

        <label>Caption</label>
        <textarea name="caption" placeholder="Write a caption..." rows="2"></textarea>

        <button type="submit" name="submit_post">Post</button>
    </form>
</div>
</section>

<!-- Feed -->
<main class="feed-container">
    <?php if (empty($posts)): ?>
        <p>No posts yet.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-user">
                    <img src="<?php echo htmlspecialchars($post['profile_picture_url'] ?? 'assets/uploads/default_avatar.png'); ?>" class="avatar-small">
                    <strong><?php echo htmlspecialchars($post['username'] ?? 'Unknown'); ?></strong>
                </div>
                <img src="<?php echo htmlspecialchars($post['image_url'] ?? ''); ?>" class="post-image">
                <p><?php echo htmlspecialchars($post['caption'] ?? ''); ?></p>

                <!-- Like -->
                <form method="post" class="like-form" data-post-id="<?php echo $post['id']; ?>">
                    <?php
                    $like_stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = :post AND user_id = :user LIMIT 1");
                    $like_stmt->execute(['post' => $post['id'], 'user' => $user_id]);
                    $liked = $like_stmt->fetch() ? true : false;
                    ?>
                    <button type="submit"><?php echo $liked ? 'Unlike' : 'Like'; ?></button>
                </form>

                <!-- Comments -->
                <div class="comments">
                    <?php
                    $comment_stmt = $pdo->prepare("
                        SELECT comments.*, users.username 
                        FROM comments 
                        JOIN users ON comments.user_id = users.id 
                        WHERE post_id = :post 
                        ORDER BY created_at ASC
                    ");
                    $comment_stmt->execute(['post' => $post['id']]);
                    $comments = $comment_stmt->fetchAll();
                    ?>
                    <?php foreach ($comments as $c): ?>
                        <div class="comment">
                            <strong><?php echo htmlspecialchars($c['username'] ?? ''); ?>:</strong>
                            <?php echo htmlspecialchars($c['comment_text'] ?? ''); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add comment -->
                <form method="post" class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                    <input type="text" name="comment_text" placeholder="Add a comment...">
                    <button type="submit">Post</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<script>
// AJAX like
document.querySelectorAll('.like-form').forEach(form => {
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const postId = form.dataset.postId;
        const fd = new FormData();
        fd.append('post_id', postId);

        const res = await fetch('api/like.php', { method: 'POST', body: fd });
        const data = await res.json();
        location.reload();
    });
});

// AJAX comment
document.querySelectorAll('.comment-form').forEach(form => {
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const postId = form.dataset.postId;
        const text = form.querySelector('[name="comment_text"]').value;
        if (!text) return;

        const fd = new FormData();
        fd.append('post_id', postId);
        fd.append('comment_text', text);

        const res = await fetch('api/comment.php', { method: 'POST', body: fd });
        const data = await res.json();
        location.reload();
    });
});

// AJAX follow
document.querySelectorAll('.follow-form').forEach(form => {
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const userId = form.dataset.userId;
        const fd = new FormData();
        fd.append('following_id', userId);

        const res = await fetch('api/follow.php', { method: 'POST', body: fd });
        const data = await res.json();
        location.reload();
    });
});
</script>
</body>
</html>

