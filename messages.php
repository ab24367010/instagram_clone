<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Instagram Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>Messages</h1>
    <nav>
        <a href="index.php">Feed</a> |
        <a href="profile.php">Profile</a> |
        <a href="auth/logout.php">Logout</a>
    </nav>
</header>

<main class="chat-container">
    <div>
        <label>Chat with User ID:</label>
        <input type="number" id="chat-user-id">
        <button onclick="loadMessages()">Load Chat</button>
    </div>

    <div id="chat-messages" class="chat-messages"></div>

    <div>
        <input type="text" id="chat-input" placeholder="Type a message">
        <button onclick="sendMessage()">Send</button>
    </div>
</main>

<script>
let currentChatUser = 0;

async function loadMessages() {
    const userId = document.getElementById('chat-user-id').value;
    currentChatUser = userId;
    const res = await fetch(`api/messages.php?user_id=${userId}`);
    const messages = await res.json();
    const container = document.getElementById('chat-messages');
    container.innerHTML = '';
    messages.forEach(msg => {
        const div = document.createElement('div');
        div.classList.add('chat-message');
        div.innerHTML = `<strong>${msg.sender_id == <?php echo $user_id; ?> ? 'You' : msg.sender_name}</strong>: ${msg.message_text}`;
        container.appendChild(div);
    });
}

async function sendMessage() {
    const text = document.getElementById('chat-input').value;
    if (!text || !currentChatUser) return;
    const formData = new FormData();
    formData.append('receiver_id', currentChatUser);
    formData.append('message_text', text);

    const res = await fetch('api/message.php', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.status === 'sent') {
        document.getElementById('chat-input').value = '';
        loadMessages();
    }
}
</script>
</body>
</html>
