<?php
session_start();
require_once __DIR__ . '/includes/database.php';

// Session check
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user info
$stmt = $pdo->prepare("SELECT username, full_name, bio, profile_picture_url FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);

    if (empty($new_username)) {
        $error = "Username хоосон байж болохгүй.";
    } else {
        // Check username uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
        $stmt->execute(['username' => $new_username, 'id' => $user_id]);
        if ($stmt->fetch()) {
            $error = "Username аль хэдийн ашиглагдаж байна.";
        } else {
            // Profile picture upload
            $profile_picture_url = $user['profile_picture_url'];
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $target_dir = __DIR__ . "/assets/uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

                $filename = uniqid() . '_' . basename($_FILES["profile_picture"]["name"]);
                $target_file = $target_dir . $filename;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed_types)) {
                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        $profile_picture_url = "assets/uploads/$filename";
                    } else {
                        $error = "Зураг хадгалах явцад алдаа гарлаа.";
                    }
                } else {
                    $error = "Зөвхөн JPG, PNG, GIF зураг оруулж болно.";
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
                $success = "Profile амжилттай шинэчлэгдлээ.";
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
        $error = "Бүх нууц үгийн талбарыг бөглөнө үү.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Шинэ нууц үг таарахгүй байна.";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $user_id]);
        $user_data = $stmt->fetch();

        if ($user_data && password_verify($current_password, $user_data['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
            $stmt->execute(['password_hash' => $new_hash, 'id' => $user_id]);
            $success = "Нууц үг амжилттай солигдлоо.";
        } else {
            $error = "Хуучин нууц үг буруу байна.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Instagram Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>Settings</h1>
    <nav>
        <a href="index.php">Feed</a> |
        <a href="profile.php">Profile</a> |
        <a href="auth/logout.php">Logout</a>
    </nav>
</header>

<main class="settings-container">
    <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    <?php if ($success) echo "<div class='success'>$success</div>"; ?>

    <section>
        <h2>Profile Settings</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label>Full Name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">

            <label>Bio</label>
            <textarea name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>

            <label>Profile Picture</label>
            <input type="file" name="profile_picture">
            <?php if ($user['profile_picture_url']): ?>
                <img src="<?php echo $user['profile_picture_url']; ?>" alt="avatar" class="avatar-small">
            <?php endif; ?>

            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </section>

    <section>
        <h2>Change Password</h2>
        <form method="post">
            <label>Current Password</label>
            <input type="password" name="current_password" required>

            <label>New Password</label>
            <input type="password" name="new_password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" name="change_password">Change Password</button>
        </form>
    </section>
</main>
</body>
</html>
