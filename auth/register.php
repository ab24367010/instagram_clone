<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Бүх талбарыг бөглөнө үү.";
    } elseif ($password !== $confirm_password) {
        $error = "Нууц үг таарахгүй байна.";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            $error = "Username эсвэл Email аль хэдийн бүртгэлтэй байна.";
        } else {
            // Insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash
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
    <title>Register - Instagram Clone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <h2>Register</h2>
    <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    <form method="post" action="">
        <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>">
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>
    <p>Бүртгэлтэй бол <a href="login.php">Login</a></p>
</div>
</body>
</html>
