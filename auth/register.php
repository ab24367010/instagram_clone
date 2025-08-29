<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$error = '';
$username = '';
$email = '';
$full_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            $error = "Username or email already exists.";
        } else {
            // Insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, profile_picture_url) VALUES (:username, :email, :password_hash, :full_name, :profile_picture)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
                'full_name' => $full_name,
                'profile_picture' => 'assets/uploads/default_avatar.png'
            ]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;

            header("Location: ../index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up • Instagram</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Instagram</h2>
            <p style="text-align: center; color: #8e8e8e; font-size: 16px; font-weight: 600; margin-bottom: 20px;">
                Sign up to see photos and videos from your friends.
            </p>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>">
                <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($full_name); ?>">
                <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Sign up</button>
            </form>
            
            <p style="text-align: center; color: #8e8e8e; font-size: 12px; margin-top: 16px; line-height: 1.4;">
                By signing up, you agree to our Terms, Data Policy and Cookies Policy.
            </p>
        </div>
        
        <div class="auth-links">
            <p>Have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
</body>
</html>