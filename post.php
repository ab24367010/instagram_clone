<?php
session_start();
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$error = '';
$success = '';

if (isset($_POST['submit_post'])) {
    $caption = trim($_POST['caption'] ?? '');
    $image_url = '';
    
    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . "/assets/uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG, and GIF files are allowed.";
        } else {
            // Check file size (limit to 5MB)
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = "File size must be less than 5MB.";
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image_url = "assets/uploads/" . $fileName;
                } else {
                    $error = "Error uploading image.";
                }
            }
        }
    }
    
    // Insert post (allow text-only posts)
    if (!$error && ($image_url || $caption)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, image_url, caption) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $image_url, $caption])) {
            $success = "Post created successfully!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error creating post.";
        }
    } elseif (!$error) {
        $error = "Please add an image or caption.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post • Instagram</title>
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

    <main style="max-width: 600px; margin: 0 auto; padding: 30px 20px;">
        <div class="settings-form">
            <h2>Create New Post</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Choose Photo</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color: #8e8e8e; font-size: 12px;">JPG, PNG, or GIF. Max size 5MB.</small>
                </div>
                
                <div class="form-group">
                    <label>Caption</label>
                    <textarea name="caption" placeholder="Write a caption..." rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" name="submit_post" class="btn-primary">Share</button>
                    <a href="index.php" style="padding: 8px 16px; color: #262626; text-decoration: none; border: 1px solid #dbdbdb; border-radius: 4px; font-weight: 600; font-size: 14px;">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>