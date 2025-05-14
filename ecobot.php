<?php
session_start();
require_once "config/database.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoBot - AI Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-container {
            max-width: 600px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 70vh;
        }
        .chat-header {
            background: #198754;
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 18px 24px;
            font-size: 1.3rem;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .chat-messages {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 24px;
            background: #f8f9fa;
        }
        .chat-message {
            margin-bottom: 18px;
            display: flex;
        }
        .chat-message.user .bubble {
            margin-left: auto;
            background: #d1e7dd;
            color: #155724;
        }
        .chat-message.bot .bubble {
            margin-right: auto;
            background: #e9ecef;
            color: #333;
        }
        .bubble {
            padding: 12px 18px;
            border-radius: 18px;
            max-width: 75%;
            font-size: 1.05em;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .chat-input {
            border-top: 1px solid #eee;
            padding: 16px 24px;
            background: #fff;
            border-radius: 0 0 16px 16px;
        }
        .chat-input form {
            display: flex;
            gap: 10px;
        }
        .chat-input input[type="text"] {
            flex: 1 1 auto;
            border-radius: 20px;
            border: 1px solid #ccc;
            padding: 10px 16px;
            font-size: 1em;
        }
        .chat-input button {
            border-radius: 20px;
            padding: 8px 24px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <div class="chat-container mt-5">
        <div class="chat-header">EcoBot <span style="font-size:0.9em;font-weight:400;">(AI Assistant)</span></div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will appear here -->
        </div>
        <div class="chat-input">
            <form id="chatForm" autocomplete="off">
                <input type="text" id="userInput" class="form-control" placeholder="Type your message..." required />
                <button type="submit" class="btn btn-success">Send</button>
            </form>
        </div>
    </div>
</div>
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>EcoMap</h5>
                <p>Making waste management smarter and more efficient.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <h5>Contact Us</h5>
                <p>Email: info@ecomap.com<br>Phone: (123) 456-7890</p>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const userInput = document.getElementById('userInput');

function appendMessage(sender, text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message ' + sender;
    msgDiv.innerHTML = `<div class="bubble">${text}</div>`;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const text = userInput.value.trim();
    if (!text) return;
    appendMessage('user', text);
    userInput.value = '';
    appendMessage('bot', '<span class="text-muted">EcoBot is typing...</span>');
    fetch('api/ecobot_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
    })
    .then(res => res.json())
    .then(data => {
        // Remove the "EcoBot is typing..." message
        const lastMsg = chatMessages.querySelector('.chat-message.bot:last-child');
        if (lastMsg) lastMsg.remove();
        appendMessage('bot', data.reply);
    })
    .catch(() => {
        const lastMsg = chatMessages.querySelector('.chat-message.bot:last-child');
        if (lastMsg) lastMsg.remove();
        appendMessage('bot', '<span class="text-danger">Sorry, EcoBot is unavailable right now.</span>');
    });
});
</script>
</body>
</html> 