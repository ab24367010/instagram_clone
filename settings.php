<?php
session_start();
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user info
$stmt = $pdo->prepare("SELECT username, email, full_name, bio, profile_picture_url FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);

    if (empty($new_username)) {
        $error = "Username cannot be empty.";
    } else {
        // Check username uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
        $stmt->execute(['username' => $new_username, 'id' => $user_id]);
        if ($stmt->fetch()) {
            $error = "Username is already taken.";
        } else {
            // Handle profile picture upload
            $profile_picture_url = $user['profile_picture_url'];
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $target_dir = __DIR__ . "/assets/uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $filename = uniqid() . '_' . basename($_FILES["profile_picture"]["name"]);
                $target_file = $target_dir . $filename;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed_types)) {
                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        $profile_picture_url = "assets/uploads/$filename";
                    } else {
                        $error = "Error uploading profile picture.";
                    }
                } else {
                    $error = "Only JPG, PNG, and GIF files are allowed.";
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("UPDATE users SET username = :username, full_name = :full_name, bio = :bio, profile_picture_url = :profile_picture WHERE id = :id");
                $stmt->execute([
                    'username' => $new_username,
                    'full_name' => $full_name,
                    'bio' => $bio,
                    'profile_picture' => $profile_picture_url,
                    'id' => $user_id
                ]);
                $_SESSION['username'] = $new_username;
                $success = "Profile updated successfully.";
                
                // Update user array for display
                $user['username'] = $new_username;
                $user['full_name'] = $full_name;
                $user['bio'] = $bio;
                $user['profile_picture_url'] = $profile_picture_url;
            }
        }
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords don't match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $user_id]);
        $user_data = $stmt->fetch();

        if ($user_data && password_verify($current_password, $user_data['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
            $stmt->execute(['password_hash' => $new_hash, 'id' => $user_id]);
            $success = "Password changed successfully.";
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile • Instagram</title>
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

    <main class="settings-container">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="settings-form">
            <h2>Edit Profile</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Profile Photo</label>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <img src="<?php echo htmlspecialchars($user['profile_picture_url']); ?>" class="avatar-small" alt="Current avatar">
                        <input type="file" name="profile_picture" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn-primary">Submit</button>
            </form>
        </div>

        <div class="settings-form">
            <h2>Change Password</h2>
            <form method="post">
                <div class="form-group">
                    <label>Old Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" name="change_password" class="btn-primary">Change Password</button>
            </form>
        </div>
    </main>
</body>
</html>