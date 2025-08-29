<?php
session_start();
require_once __DIR__ . '/includes/database.php';

// Login check
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Feed posts (own + following)
$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_picture_url,
           COUNT(likes.id) as like_count
    FROM posts
    JOIN users ON posts.user_id = users.id
    LEFT JOIN likes ON posts.id = likes.post_id
    WHERE posts.user_id = :uid1
       OR posts.user_id IN (
           SELECT following_id FROM followers WHERE follower_id = :uid2
       )
    GROUP BY posts.id
    ORDER BY posts.created_at DESC
");
$stmt->execute(['uid1' => $user_id, 'uid2' => $user_id]);
$posts = $stmt->fetchAll();

// Handle search
$search_results = [];
if (!empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt_search = $pdo->prepare("
        SELECT id, username, profile_picture_url
        FROM users
        WHERE username LIKE :search
          AND id != :uid
        LIMIT 10
    ");
    $stmt_search->execute(['search' => $search, 'uid' => $user_id]);
    $search_results = $stmt_search->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Clone</title>
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

    <main>
        <!-- Search Section -->
        <section class="search-follow">
            <form method="get" action="index.php" class="search-form">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button type="submit">Search</button>
            </form>

            <?php if (!empty($search_results)): ?>
                <div class="search-results">
                    <h3>Search Results:</h3>
                    <ul>
                        <?php foreach ($search_results as $u): ?>
                            <li>
                                <img src="<?php echo htmlspecialchars($u['profile_picture_url'] ?? 'assets/uploads/default_avatar.png'); ?>" class="avatar-small" alt="avatar">
                                <div>
                                    <a href="profile.php?id=<?php echo $u['id']; ?>" style="text-decoration: none; color: #262626; font-weight: 600;">
                                        <?php echo htmlspecialchars($u['username']); ?>
                                    </a>
                                </div>
                                <form method="post" class="follow-form" data-user-id="<?php echo $u['id']; ?>" style="margin-left: auto;">
                                    <button type="submit">Follow</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>

        <!-- New Post Form -->
        <section class="new-post">
            <h3>Create New Post</h3>
            <form action="post.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Choose Photo</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label>Write a caption...</label>
                    <textarea name="caption" placeholder="Write a caption..."></textarea>
                </div>
                
                <button type="submit" name="submit_post">Share Post</button>
            </form>
        </section>

        <!-- Feed -->
        <?php if (empty($posts)): ?>
            <div class="post">
                <div style="padding: 40px; text-align: center; color: #8e8e8e;">
                    <p>No posts in your feed yet. Follow some users or create your first post!</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="post">
                    <div class="post-header">
                        <img src="<?php echo htmlspecialchars($post['profile_picture_url'] ?? 'assets/uploads/default_avatar.png'); ?>" class="avatar" alt="avatar">
                        <div class="post-user">
                            <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($post['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" alt="post">
                    <?php endif; ?>
                    
                    <div class="post-actions">
                        <?php
                        $like_stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = :post AND user_id = :user LIMIT 1");
                        $like_stmt->execute(['post' => $post['id'], 'user' => $user_id]);
                        $liked = $like_stmt->fetch() ? true : false;
                        ?>
                        <form method="post" class="like-form" data-post-id="<?php echo $post['id']; ?>" style="display: inline;">
                            <button type="submit"><?php echo $liked ? '♥ Unlike' : '♡ Like'; ?></button>
                        </form>
                        <span style="color: #8e8e8e; font-size: 14px; margin-left: 8px;">
                            <?php echo $post['like_count']; ?> <?php echo $post['like_count'] == 1 ? 'like' : 'likes'; ?>
                        </span>
                    </div>
                    
                    <?php if ($post['caption']): ?>
                        <div class="post-caption">
                            <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                            <?php echo nl2br(htmlspecialchars($post['caption'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-timestamp">
                        <?php echo strtoupper(date('M d, Y', strtotime($post['created_at']))); ?>
                    </div>

                    <!-- Comments -->
                    <div class="comments">
                        <?php
                        $comment_stmt = $pdo->prepare("
                            SELECT comments.*, users.username 
                            FROM comments 
                            JOIN users ON comments.user_id = users.id 
                            WHERE post_id = :post 
                            ORDER BY created_at ASC
                            LIMIT 5
                        ");
                        $comment_stmt->execute(['post' => $post['id']]);
                        $comments = $comment_stmt->fetchAll();
                        ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <span class="username"><?php echo htmlspecialchars($comment['username']); ?></span>
                                <?php echo htmlspecialchars($comment['comment_text']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Add comment -->
                    <form method="post" class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                        <input type="text" name="comment_text" placeholder="Add a comment..." required>
                        <button type="submit">Post</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
        // AJAX like functionality
        document.querySelectorAll('.like-form').forEach(form => {
            form.addEventListener('submit', async e => {
                e.preventDefault();
                const postId = form.dataset.postId;
                const formData = new FormData();
                formData.append('post_id', postId);

                try {
                    const response = await fetch('api/like.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.status) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error liking post:', error);
                }
            });
        });

        // AJAX comment functionality
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', async e => {
                e.preventDefault();
                const postId = form.dataset.postId;
                const textInput = form.querySelector('[name="comment_text"]');
                const text = textInput.value.trim();
                
                if (!text) return;

                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('comment_text', text);

                try {
                    const response = await fetch('api/comment.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error posting comment:', error);
                }
            });
        });

        // AJAX follow functionality
        document.querySelectorAll('.follow-form').forEach(form => {
            form.addEventListener('submit', async e => {
                e.preventDefault();
                const userId = form.dataset.userId;
                const formData = new FormData();
                formData.append('profile_id', userId);

                try {
                    const response = await fetch('api/follow.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.status) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error following user:', error);
                }
            });
        });
    </script>
</body>
</html>