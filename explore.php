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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore • Instagram</title>
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

    <main class="explore-container">
        <h2 style="margin-bottom: 20px; font-weight: 600; text-align: center;">Explore</h2>
        <div id="explore-posts" class="explore-grid"></div>
        
        <div id="loading" style="text-align: center; padding: 40px; color: #8e8e8e;">
            Loading posts...
        </div>
    </main>

    <script>
        async function loadExplore() {
            try {
                const response = await fetch('api/explore.php');
                const posts = await response.json();
                const container = document.getElementById('explore-posts');
                const loading = document.getElementById('loading');
                
                loading.style.display = 'none';
                container.innerHTML = '';
                
                if (posts.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 60px 0; color: #8e8e8e; grid-column: 1 / -1;">No posts to explore yet.</div>';
                    return;
                }
                
                posts.forEach(post => {
                    const div = document.createElement('div');
                    div.classList.add('explore-post');
                    div.onclick = () => openPostDetail(post);
                    
                    if (post.image_url) {
                        div.innerHTML = `
                            <img src="${post.image_url}" alt="Post by ${post.username}">
                            <div style="position: absolute; inset: 0; background: linear-gradient(transparent 60%, rgba(0,0,0,0.8)); display: flex; align-items: end; padding: 12px; opacity: 0; transition: opacity 0.2s;" class="post-overlay">
                                <div style="color: white; font-size: 12px;">
                                    <div style="font-weight: 600;">@${post.username}</div>
                                    <div style="margin-top: 4px; opacity: 0.9;">${post.caption ? post.caption.substring(0, 50) + (post.caption.length > 50 ? '...' : '') : ''}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        div.innerHTML = `
                            <div style="background: #f8f8f8; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 20px; text-align: center;">
                                <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">@${post.username}</div>
                                <div style="color: #8e8e8e; font-size: 12px;">${post.caption || 'Text post'}</div>
                            </div>
                        `;
                    }
                    
                    // Add hover effect
                    div.addEventListener('mouseenter', () => {
                        const overlay = div.querySelector('.post-overlay');
                        if (overlay) overlay.style.opacity = '1';
                    });
                    
                    div.addEventListener('mouseleave', () => {
                        const overlay = div.querySelector('.post-overlay');
                        if (overlay) overlay.style.opacity = '0';
                    });
                    
                    container.appendChild(div);
                });
            } catch (error) {
                console.error('Error loading explore posts:', error);
                document.getElementById('loading').innerHTML = 'Error loading posts. Please try again.';
            }
        }
        
        function openPostDetail(post) {
            // Simple alert for now - in a real app this would open a modal
            alert(`Post by @${post.username}\n\n${post.caption || 'No caption'}`);
        }

        // Load posts when page loads
        loadExplore();
    </script>
</body>
</html>