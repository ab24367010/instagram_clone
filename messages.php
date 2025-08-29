<?php
session_start();
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get users that current user has messaged with
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.profile_picture_url,
           (SELECT message_text FROM messages 
            WHERE (sender_id = u.id AND receiver_id = :uid1) 
               OR (sender_id = :uid2 AND receiver_id = u.id)
            ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM messages 
            WHERE (sender_id = u.id AND receiver_id = :uid3) 
               OR (sender_id = :uid4 AND receiver_id = u.id)
            ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT sender_id FROM messages WHERE receiver_id = :uid5
        UNION
        SELECT DISTINCT receiver_id FROM messages WHERE sender_id = :uid6
    )
    AND u.id != :uid7
    ORDER BY last_message_time DESC
");

$stmt->execute([
    ':uid1' => $user_id,
    ':uid2' => $user_id,
    ':uid3' => $user_id,
    ':uid4' => $user_id,
    ':uid5' => $user_id,
    ':uid6' => $user_id,
    ':uid7' => $user_id
]);
$conversations = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct • Instagram</title>
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

    <main class="chat-container">
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; height: 500px;">
            
            <!-- Conversations List -->
            <div style="background: #ffffff; border: 1px solid #dbdbdb; border-radius: 8px; overflow: hidden;">
                <div style="padding: 16px; border-bottom: 1px solid #dbdbdb; font-weight: 600; text-align: center;">
                    Direct Messages
                </div>
                
                <div class="chat-header" style="border: none; border-radius: 0; margin: 0;">
                    <input type="text" id="search-users" placeholder="Search users..." style="width: 100%; margin: 0;">
                    <div id="user-search-results" style="margin-top: 10px; max-height: 150px; overflow-y: auto;"></div>
                </div>
                
                <div style="overflow-y: auto; height: calc(100% - 120px);">
                    <?php if (empty($conversations)): ?>
                        <div style="padding: 20px; text-align: center; color: #8e8e8e;">
                            No conversations yet.<br>
                            Search for users above to start chatting.
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="conversation-item" onclick="loadChat(<?php echo $conv['id']; ?>, '<?php echo htmlspecialchars($conv['username']); ?>')" 
                                 style="padding: 12px 16px; border-bottom: 1px solid #efefef; cursor: pointer; display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo htmlspecialchars($conv['profile_picture_url'] ?? 'assets/uploads/default_avatar.png'); ?>" 
                                     class="avatar" alt="<?php echo htmlspecialchars($conv['username']); ?>">
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($conv['username']); ?></div>
                                    <div style="color: #8e8e8e; font-size: 12px; margin-top: 2px;">
                                        <?php 
                                        echo $conv['last_message'] ? 
                                            (strlen($conv['last_message']) > 30 ? substr($conv['last_message'], 0, 30) . '...' : $conv['last_message']) : 
                                            'Start conversation'; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Messages -->
            <div style="background: #ffffff; border: 1px solid #dbdbdb; border-radius: 8px; display: flex; flex-direction: column;">
                <div id="chat-header" style="padding: 16px; border-bottom: 1px solid #dbdbdb; font-weight: 600; text-align: center; background: #fafafa;">
                    Select a conversation
                </div>
                
                <div id="chat-messages" class="chat-messages" style="flex: 1; margin: 0; border: none; border-radius: 0;">
                    <div style="text-align: center; padding: 40px; color: #8e8e8e;">
                        Choose a conversation to start messaging
                    </div>
                </div>
                
                <div id="chat-input-container" style="padding: 16px; border-top: 1px solid #dbdbdb; display: none;">
                    <form class="chat-input-form" onsubmit="sendMessage(event)">
                        <input type="text" id="chat-input" placeholder="Message..." required>
                        <button type="submit">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        let currentChatUser = 0;

        // Search for users
        document.getElementById('search-users').addEventListener('input', async function() {
            const query = this.value.trim();
            const resultsContainer = document.getElementById('user-search-results');
            
            if (query.length < 2) {
                resultsContainer.innerHTML = '';
                return;
            }
            
            try {
                const response = await fetch(`api/search_user.php?q=${encodeURIComponent(query)}`);
                const users = await response.json();
                
                resultsContainer.innerHTML = '';
                users.forEach(user => {
                    const div = document.createElement('div');
                    div.style.cssText = 'padding: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #efefef;';
                    div.innerHTML = `
                        <img src="${user.profile_picture_url || 'assets/uploads/default_avatar.png'}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                        <span style="font-size: 13px;">${user.username}</span>
                    `;
                    div.onclick = () => {
                        loadChat(user.id, user.username);
                        resultsContainer.innerHTML = '';
                        document.getElementById('search-users').value = '';
                    };
                    resultsContainer.appendChild(div);
                });
            } catch (error) {
                console.error('Error searching users:', error);
            }
        });

        async function loadChat(userId, username) {
    currentChatUser = userId;
    
    document.getElementById('chat-header').textContent = username;
    document.getElementById('chat-input-container').style.display = 'block';
    
    try {
        const response = await fetch(`api/messages.php?user_id=${userId}`);
        const messages = await response.json();
        const container = document.getElementById('chat-messages');
        
        container.innerHTML = '';
        
        if (!Array.isArray(messages)) {
            console.error('Server returned error:', messages);
            container.innerHTML = `<div style="color:red; text-align:center; padding:20px;">
                Error loading messages: ${messages.error || 'Unknown error'}
            </div>`;
            return;
        }
        
        if (messages.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #8e8e8e;">No messages yet. Say hello!</div>';
        } else {
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.classList.add('chat-message');
                const isOwn = msg.sender_id == <?php echo $user_id; ?>;
                div.style.textAlign = isOwn ? 'right' : 'left';
                div.innerHTML = `
                    <div style="display: inline-block; max-width: 70%; padding: 8px 12px; border-radius: 18px; background: ${isOwn ? '#0095f6' : '#efefef'}; color: ${isOwn ? 'white' : '#262626'};">
                        ${msg.message_text}
                    </div>
                    <div style="font-size: 11px; color: #8e8e8e; margin-top: 4px;">
                        ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                `;
                container.appendChild(div);
            });
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}


        async function sendMessage(event) {
            event.preventDefault();
            
            const input = document.getElementById('chat-input');
            const text = input.value.trim();
            
            if (!text || !currentChatUser) return;
            
            const formData = new FormData();
            formData.append('receiver_id', currentChatUser);
            formData.append('message_text', text);

            try {
                const response = await fetch('api/message.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'sent') {
                    input.value = '';
                    // Reload the chat to show new message
                    const username = document.getElementById('chat-header').textContent;
                    loadChat(currentChatUser, username);
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        }
    </script>
</body>
</html>