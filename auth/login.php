<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email болон нууц үгээ оруулна уу.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: ../index.php");
            exit;
        } else {
            $error = "Email эсвэл нууц үг буруу байна.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Instagram Clone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <h2>Login</h2>
    <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p>Шинээр бүртгүүлэх бол <a href="register.php">Register</a> | Нууц үгээ мартсан бол <a href="forgot_password.php">Forgot Password</a></p>
</div>
</body>
</html>
