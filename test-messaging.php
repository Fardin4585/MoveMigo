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
    <title>Test Messaging - MoveMigo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .conversation {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .conversation h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .conversation p {
            margin: 5px 0;
            color: #666;
        }
        .unread {
            background: #e3f2fd;
            border-left: 4px solid #007bff;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-comments"></i> Messaging Test Page</h1>
            <p>User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> (<?php echo ucfirst($user['user_type']); ?>)</p>
        </div>

        <div class="stats">
            <h3>Statistics</h3>
            <p><strong>Total Conversations:</strong> <?php echo count($conversations); ?></p>
            <p><strong>Unread Messages:</strong> <?php echo $unread_count; ?></p>
        </div>

        <div>
            <a href="messages.php" class="btn">
                <i class="fas fa-comments"></i> Go to Messages
            </a>
            <a href="<?php echo $user['user_type'] === 'tenant' ? 'tenant-dashboard.php' : 'homeowner-dashboard.php'; ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <h2>Your Conversations</h2>
        <?php if (empty($conversations)): ?>
            <div class="conversation">
                <h3>No conversations yet</h3>
                <p>Start a conversation by messaging a property owner or tenant.</p>
            </div>
        <?php else: ?>
            <?php foreach ($conversations as $conversation): ?>
                <div class="conversation <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>">
                    <h3>
                        <?php echo htmlspecialchars($conversation['other_user_name']); ?>
                        <?php if ($conversation['unread_count'] > 0): ?>
                            <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">
                                <?php echo $conversation['unread_count']; ?> unread
                            </span>
                        <?php endif; ?>
                    </h3>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($conversation['other_user_email']); ?></p>
                    <?php if ($conversation['home_name']): ?>
                        <p><strong>Property:</strong> <?php echo htmlspecialchars($conversation['home_name']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($conversation['address'] . ', ' . $conversation['city']); ?></p>
                    <?php endif; ?>
                    <p><strong>Last Message:</strong> <?php echo htmlspecialchars(substr($conversation['last_message'] ?? 'No messages yet', 0, 100)); ?></p>
                    <p><strong>Last Activity:</strong> <?php echo date('M j, Y g:i A', strtotime($conversation['last_message_at'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2>API Test</h2>
        <div>
            <button class="btn" onclick="testGetConversations()">Test Get Conversations</button>
            <button class="btn" onclick="testGetUnreadCount()">Test Get Unread Count</button>
        </div>
        <div id="api-results" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; display: none;">
            <h4>API Results:</h4>
            <pre id="api-output"></pre>
        </div>
    </div>

    <script>
        const sessionToken = '<?php echo $_SESSION['session_token'] ?? ''; ?>';

        function testGetConversations() {
            fetch('api/messaging.php?action=conversations', {
                headers: {
                    'Authorization': `Bearer ${sessionToken}`
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('api-results').style.display = 'block';
                document.getElementById('api-output').textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                document.getElementById('api-results').style.display = 'block';
                document.getElementById('api-output').textContent = 'Error: ' + error.message;
            });
        }

        function testGetUnreadCount() {
            fetch('api/messaging.php?action=unread_count', {
                headers: {
                    'Authorization': `Bearer ${sessionToken}`
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('api-results').style.display = 'block';
                document.getElementById('api-output').textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                document.getElementById('api-results').style.display = 'block';
                document.getElementById('api-output').textContent = 'Error: ' + error.message;
            });
        }
    </script>
</body>
</html> 