<?php
session_start();
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if (isset($_POST['submit_post'])) {
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . "/assets/uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Зөвхөн JPG, PNG, GIF upload хийж болно.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_url = "assets/uploads/" . $fileName;
                $caption = $_POST['caption'] ?? '';

                $stmt = $pdo->prepare("INSERT INTO posts (user_id, image_url, caption) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $image_url, $caption]);

                header("Location: index.php");
                exit;
            } else {
                $error = "Зураг хадгалах явцад алдаа гарлаа.";
            }
        }
    } else {
        $error = "Зураг сонгох хэрэгтэй.";
    }
}
?>
