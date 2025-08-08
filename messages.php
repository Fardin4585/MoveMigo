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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                    $unread_count > 0): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
                <a href="<?php echo $user['user_type'] === 'tenant' ? 'tenant-dashboard.php' : 'homeowner-dashboard.php'; ?>" class="back-btn">
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
                        <div class="conversation-last-message">Start a conversation by messaging a property owner or tenant</div>
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
                    <textarea class="message-input" id="message-input" placeholder="Type your message..." rows="1"></textarea>
                    <button class="send-btn" id="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let currentReceiverId = null;
        let currentReceiverType = null;
        const sessionToken = '<?php echo $_SESSION['session_token'] ?? ''; ?>';

        // Check if user came from clicking a message button or opening a conversation
        window.addEventListener('DOMContentLoaded', function() {
            const propertyId = sessionStorage.getItem('messagePropertyId');
            const homeownerId = sessionStorage.getItem('messageHomeownerId');
            const homeownerName = sessionStorage.getItem('messageHomeownerName');
            const openConversationId = sessionStorage.getItem('openConversationId');
            const openConversationUser = sessionStorage.getItem('openConversationUser');
            
            if (propertyId && homeownerId && homeownerName) {
                // Show new conversation option
                const newConversationItem = document.getElementById('new-conversation-item');
                const noConversationsItem = document.getElementById('no-conversations-item');
                
                if (newConversationItem && noConversationsItem) {
                    newConversationItem.style.display = 'block';
                    noConversationsItem.style.display = 'none';
                    
                    // Update the new conversation item
                    newConversationItem.querySelector('.conversation-name').textContent = `Message ${homeownerName}`;
                    newConversationItem.querySelector('.conversation-last-message').textContent = 'Start a new conversation about this property';
                    
                    // Add click handler for new conversation
                    newConversationItem.addEventListener('click', function() {
                        startNewConversation(propertyId, homeownerId, homeownerName);
                        
                        // Update active state
                        document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
                        this.classList.add('active');
                    });
                }
                
                // Clear session storage
                sessionStorage.removeItem('messagePropertyId');
                sessionStorage.removeItem('messageHomeownerId');
                sessionStorage.removeItem('messageHomeownerName');
            }
            
            // Handle opening existing conversation from homeowner dashboard
            if (openConversationId && openConversationUser) {
                // Find the conversation item and click it
                const conversationItem = document.querySelector(`[data-conversation-id="${openConversationId}"]`);
                if (conversationItem) {
                    conversationItem.click();
                }
                
                // Clear session storage
                sessionStorage.removeItem('openConversationId');
                sessionStorage.removeItem('openConversationUser');
            }
        });

        // Load conversations
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', function() {
                const conversationId = this.dataset.conversationId;
                const otherUserId = this.dataset.otherUserId;
                const otherUserType = this.dataset.otherUserType;
                
                if (conversationId) {
                    loadConversation(conversationId, otherUserId, otherUserType);
                    
                    // Update active state
                    document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });

        function startNewConversation(propertyId, homeownerId, homeownerName) {
            // Set the current receiver info
            currentReceiverId = parseInt(homeownerId);
            currentReceiverType = 'homeowner';
            
            // Update chat title
            document.getElementById('chat-title').textContent = `New Message to ${homeownerName}`;
            
            // Show chat input
            document.getElementById('chat-input').style.display = 'flex';
            
            // Clear messages area
            document.getElementById('chat-messages').innerHTML = `
                <div class="no-conversation">
                    <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <div>Start a conversation with ${homeownerName} about this property</div>
                </div>
            `;
        }

        function loadConversation(conversationId, receiverId, receiverType) {
            currentConversationId = conversationId;
            currentReceiverId = receiverId;
            currentReceiverType = receiverType;
            
            // Update chat title
            const conversationItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
            const userName = conversationItem.querySelector('.conversation-name').textContent.split(' ')[0];
            document.getElementById('chat-title').textContent = `Chat with ${userName}`;
            
            // Show chat input
            document.getElementById('chat-input').style.display = 'flex';
            
            // Load messages
            loadMessages(conversationId);
        }

        function loadMessages(conversationId) {
            fetch(`api/messaging.php?action=messages&conversation_id=${conversationId}`, {
                headers: {
                    'Authorization': `Bearer ${sessionToken}`
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.messages);
                } else {
                    console.error('Error loading messages:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function displayMessages(messages) {
            const chatMessages = document.getElementById('chat-messages');
            const currentUserId = <?php echo $user['user_id']; ?>;
            const currentUserType = '<?php echo $user['user_type']; ?>';
            
            if (messages.length === 0) {
                chatMessages.innerHTML = '<div class="no-conversation">No messages yet. Start the conversation!</div>';
                return;
            }
            
            chatMessages.innerHTML = '';
            
            // Reverse messages to show oldest first
            messages.reverse().forEach(message => {
                const isSent = message.sender_id == currentUserId && message.sender_type === currentUserType;
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
                
                const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                messageDiv.innerHTML = `
                    <div class="message-content">${message.content}</div>
                    <div class="message-time">${time}</div>
                `;
                
                chatMessages.appendChild(messageDiv);
            });
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Send message
        document.getElementById('send-btn').addEventListener('click', sendMessage);
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Debug function to check current state
        function debugState() {
            console.log('Current state:', {
                conversationId: currentConversationId,
                receiverId: currentReceiverId,
                receiverType: currentReceiverType,
                sessionToken: sessionToken ? 'Present' : 'Missing'
            });
        }

        function sendMessage() {
            const messageInput = document.getElementById('message-input');
            const content = messageInput.value.trim();
            if (!content) return;
            // If we don't have a conversation ID, this is a new conversation
            if (!currentConversationId) {
                // Use the propertyId and homeownerId from the new conversation context
                const propertyId = sessionStorage.getItem('messagePropertyId') || window.lastPropertyId;
                const homeownerId = sessionStorage.getItem('messageHomeownerId') || window.lastHomeownerId;
                const homeownerName = sessionStorage.getItem('messageHomeownerName') || window.lastHomeownerName;
                const data = {
                    action: 'send_message',
                    receiver_id: currentReceiverId || homeownerId,
                    receiver_type: currentReceiverType || 'homeowner',
                    content: content,
                    home_id: propertyId ? parseInt(propertyId) : null
                };
                fetch('api/messaging.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${sessionToken}`
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        currentConversationId = data.conversation_id;
                        // After first message, load the conversation so user can continue chatting
                        loadConversation(currentConversationId, data.receiver_id || homeownerId, 'homeowner');
                    } else {
                        console.error('Error sending message:', data.error);
                        alert('Error sending message: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending message');
                });
            } else {
                // Existing conversation
                const data = {
                    action: 'send_message',
                    receiver_id: currentReceiverId,
                    receiver_type: currentReceiverType,
                    content: content
                };
                fetch('api/messaging.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${sessionToken}`
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        loadMessages(currentConversationId);
                    } else {
                        console.error('Error sending message:', data.error);
                        alert('Error sending message: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending message');
                });
            }
        }

        // Auto-resize textarea
        document.getElementById('message-input').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    </script>
</body>
</html> 