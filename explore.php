<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Explore - Instagram Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>Explore</h1>
    <nav>
        <a href="index.php">Feed</a> |
        <a href="profile.php">Profile</a> |
        <a href="auth/logout.php">Logout</a>
    </nav>
</header>

<main class="explore-container">
    <div id="explore-posts"></div>
</main>

<script>
async function loadExplore() {
    const res = await fetch('api/explore.php');
    const posts = await res.json();
    const container = document.getElementById('explore-posts');
    container.innerHTML = '';
    posts.forEach(post => {
        const div = document.createElement('div');
        div.classList.add('post');
        div.innerHTML = `
            <div class="post-user">
                <img src="${post.profile_picture_url}" class="avatar-small">
                <strong>${post.username}</strong>
            </div>
            <img src="${post.image_url}" class="post-image">
            <p>${post.caption || ''}</p>
        `;
        container.appendChild(div);
    });
}

loadExplore();
</script>
</body>
</html>
