<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Зөв email оруулна уу.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Normally, generate reset token and email user
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id");
            $stmt->execute(['token' => $token, 'id' => $user['id']]);

            // TODO: Send email to user with reset link
            $message = "Нууц үг сэргээх холбоос таны email рүү илгээгдлээ.";
        } else {
            $message = "Тухайн email бүртгэлгүй байна.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Instagram Clone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <h2>Forgot Password</h2>
    <?php if ($message) echo "<div class='info'>$message</div>"; ?>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <p>Back to <a href="login.php">Login</a></p>
</div>
</body>
</html>
