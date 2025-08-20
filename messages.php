<?php
session_start();
require_once 'includes/Auth.php';
require_once 'includes/Messaging.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: signin.php');
    exit();
}

$auth = new Auth();
$user = $auth->validateSession($_SESSION['session_token'] ?? '');

if (!$user) {
    session_destroy();
    header('Location: signin.php');
    exit();
}

$messaging = new Messaging();
$conversations = $messaging->getConversations($user['user_id'], $user['user_type']);
$unread_count = $messaging->getUnreadCount($user['user_id'], $user['user_type']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MoveMigo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #17a2b8;
            --accent: #ffc107;
            --danger: #dc3545;
            --success: #28a745;
            --bg: #f5f5f5;
            --white: #ffffff;
            --dark: #343a40;
            --light: #f8f9fa;
            --border: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg);
            color: var(--dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--primary);
            font-size: 2rem;
        }

        .back-btn {
            background: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #0056b3;
        }

        .messaging-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: 70vh;
        }

        .conversations-list {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .conversations-header {
            background: var(--primary);
            color: var(--white);
            padding: 15px;
            font-weight: bold;
        }

        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.3s;
        }

        .conversation-item:hover {
            background: var(--light);
        }

        .conversation-item.active {
            background: var(--primary);
            color: var(--white);
        }

        .conversation-item.unread {
            background: #e3f2fd;
            border-left: 4px solid var(--primary);
        }

        .conversation-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .conversation-last-message {
            font-size: 0.9rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-item.active .conversation-last-message {
            color: #e0e0e0;
        }

        .unread-badge {
            background: var(--danger);
            color: var(--white);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .chat-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: var(--primary);
            color: var(--white);
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            max-height: 400px;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .message.sent {
            align-items: flex-end;
        }

        .message.received {
            align-items: flex-start;
        }

        .message-content {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            word-wrap: break-word;
        }

        .message.sent .message-content {
            background: var(--primary);
            color: var(--white);
        }

        .message.received .message-content {
            background: var(--light);
            color: var(--dark);
        }

        .message-time {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }

        .chat-input {
            padding: 15px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 20px;
            outline: none;
            resize: none;
        }

        .send-btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .send-btn:hover {
            background: #0056b3;
        }

        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            font-style: italic;
        }

        .property-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .messaging-container {
                grid-template-columns: 1fr;
                height: auto;
            }

            .conversations-list {
                max-height: 300px;
                overflow-y: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-comments"></i> Messages</h1>
            <div>
                <?php if (
                    $unread_count > 0
                ): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
                <a href="<?php echo $user['user_type'] === 'tenant' ? 'tenant-dashboard.php' : 'homeowner-dashboard.php'; ?>"
                    class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="messaging-container">
            <div class="conversations-list">
                <div class="conversations-header">
                    Conversations
                </div>
                <?php if (empty($conversations)): ?>
                    <div class="conversation-item" id="new-conversation-item" style="display: none;">
                        <div class="conversation-name">New Message</div>
                        <div class="conversation-last-message">Start a new conversation</div>
                    </div>
                    <div class="conversation-item" id="no-conversations-item">
                        <div class="conversation-name">No conversations yet</div>
                        <div class="conversation-last-message">Start a conversation by messaging a property owner or tenant
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <div class="conversation-item <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>"
                            data-conversation-id="<?php echo $conversation['id']; ?>"
                            data-other-user-id="<?php echo $user['user_type'] === 'tenant' ? $conversation['homeowner_id'] : $conversation['tenant_id']; ?>"
                            data-other-user-type="<?php echo $user['user_type'] === 'tenant' ? 'homeowner' : 'tenant'; ?>">
                            <div class="conversation-name">
                                <?php echo htmlspecialchars($conversation['other_user_name']); ?>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-last-message">
                                <?php echo htmlspecialchars(substr($conversation['last_message'] ?? 'No messages yet', 0, 50)); ?>
                            </div>
                            <?php if ($conversation['home_name']): ?>
                                <div class="property-info">
                                    <i class="fas fa-home"></i> <?php echo htmlspecialchars($conversation['home_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-container">
                <div class="chat-header">
                    <div id="chat-title">Select a conversation to start messaging</div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="no-conversation">
                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 10px;"></i>
                        <div>Select a conversation from the list to start messaging</div>
                    </div>
                </div>
                <div class="chat-input" id="chat-input" style="display: none;">
                    <textarea class="message-input" id="message-input" placeholder="Type your message..."
                        rows="1"></textarea>
                    <button class="send-btn" id="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script>
        const currentUserId = <?php echo $user['user_id']; ?>;
        const currentUserType = '<?php echo $user['user_type']; ?>';
        const sessionToken = '<?php echo $_SESSION['session_token'] ?? ''; ?>';

        let currentConversationId = null;
        let currentReceiverId = null;
        let currentReceiverType = null;

        const chatMessages = document.getElementById('chat-messages');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');

        // ------------------- On Page Load -------------------
        window.addEventListener('DOMContentLoaded', () => {
            const propertyId = sessionStorage.getItem('messagePropertyId');
            const homeownerId = sessionStorage.getItem('messageHomeownerId');
            const homeownerName = sessionStorage.getItem('messageHomeownerName');
            const openConversationId = sessionStorage.getItem('openConversationId');
            const openConversationUser = sessionStorage.getItem('openConversationUser');

            // Start a new conversation directly from property page
            if (propertyId && homeownerId && homeownerName) {
                startNewConversation(propertyId, homeownerId, homeownerName);
                sessionStorage.removeItem('messagePropertyId');
                sessionStorage.removeItem('messageHomeownerId');
                sessionStorage.removeItem('messageHomeownerName');
            }

            // Auto-open an existing conversation (from dashboard)
            if (openConversationId && openConversationUser) {
                const conversationItem = document.querySelector(
                    `[data-conversation-id="${openConversationId}"]`
                );
                if (conversationItem) {
                    openConversation(conversationItem);
                }
                sessionStorage.removeItem('openConversationId');
                sessionStorage.removeItem('openConversationUser');
            }
        });

        // ------------------- Event Delegation -------------------
        document.querySelector('.conversations-list').addEventListener('click', e => {
            const item = e.target.closest('.conversation-item');
            if (!item) return;
            openConversation(item);
        });

        // ------------------- Open Existing or New Conversation -------------------
        function openConversation(item) {
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            const conversationId = item.dataset.conversationId;
            const otherUserId = item.dataset.otherUserId;
            const otherUserType = item.dataset.otherUserType;

            if (conversationId) {
                // Existing conversation
                currentConversationId = conversationId;
                currentReceiverId = otherUserId;
                currentReceiverType = otherUserType;

                document.getElementById('chat-title').textContent =
                    `Chat with ${item.querySelector('.conversation-name').textContent.trim()}`;
                document.getElementById('chat-input').style.display = 'flex';
                document.getElementById('chat-messages').innerHTML =
                    '<div class="no-conversation">Loading messages...</div>';

                loadMessages(conversationId);
            } else if (otherUserId && otherUserType) {
                // Brand new conversation
                startNewConversation(null, otherUserId, item.querySelector('.conversation-name').textContent.trim());
            }
        }

        // ------------------- Start New Conversation -------------------
        function startNewConversation(propertyId, homeownerId, homeownerName) {
            currentConversationId = null;
            currentReceiverId = parseInt(homeownerId);
            currentReceiverType = 'homeowner';

            document.getElementById('chat-title').textContent = `New Message to ${homeownerName}`;
            document.getElementById('chat-input').style.display = 'flex';
            chatMessages.innerHTML = `
        <div class="no-conversation">
            <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 10px;"></i>
            <div>Start a conversation with ${homeownerName} about this property</div>
        </div>
    `;

            fetch('api/messaging.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${sessionToken}`
                },
                body: JSON.stringify({
                    action: 'get_or_create_conversation',
                    receiver_id: homeownerId,
                    receiver_type: 'homeowner',
                    property_id: propertyId
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        currentConversationId = data.conversation_id;
                        loadMessages(currentConversationId);
                    } else {
                        alert('Failed to start conversation: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Error starting conversation:', err);
                    alert('Error starting conversation');
                });
        }

        // ------------------- Load Messages -------------------
        function loadMessages(conversationId) {
            chatMessages.innerHTML = `
        <div class="no-conversation">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
            <div>Loading messages...</div>
        </div>
    `;

            fetch(`api/messaging.php?action=messages&conversation_id=${conversationId}`, {
                headers: { 'Authorization': `Bearer ${sessionToken}` }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    } else {
                        chatMessages.innerHTML = `<div class="no-conversation">Error loading messages</div>`;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    chatMessages.innerHTML = `<div class="no-conversation">Error loading messages</div>`;
                });
        }

        // ------------------- Display Messages -------------------
        function displayMessages(messages) {
            chatMessages.innerHTML = '';

            if (!messages || messages.length === 0) {
                chatMessages.innerHTML = '<div class="no-conversation">No messages yet. Start the conversation!</div>';
                return;
            }

            messages.reverse().forEach(msg => {
                const isSent = msg.sender_id == currentUserId && msg.sender_type === currentUserType;
                const msgDiv = document.createElement('div');
                msgDiv.className = `message ${isSent ? 'sent' : 'received'}`;

                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                msgDiv.innerHTML = `
            <div class="message-content">${msg.content}</div>
            <div class="message-time">${time}</div>
        `;
                chatMessages.appendChild(msgDiv);
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // ------------------- Send Message -------------------
        function sendMessage() {
            const content = messageInput.value.trim();
            if (!content) return;

            if (!currentConversationId || !currentReceiverId || !currentReceiverType) {
                console.error('Missing conversation information');
                return;
            }

            fetch('api/messaging.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${sessionToken}`
                },
                body: JSON.stringify({
                    action: 'send_message',
                    conversation_id: currentConversationId,
                    sender_id: currentUserId,
                    sender_type: currentUserType,
                    receiver_id: currentReceiverId,
                    receiver_type: currentReceiverType,
                    content: content
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        loadMessages(currentConversationId);
                    } else {
                        alert('Error sending message: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Error sending message:', err);
                    alert('Error sending message');
                });
        }

        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea
        messageInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    </script>

</body>

</html>
