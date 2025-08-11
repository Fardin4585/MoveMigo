<?php
session_start();
require_once 'includes/Auth.php';
require_once 'includes/Messaging.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    die("Please log in as a tenant first");
}

$auth = new Auth();
$user = $auth->validateSession($_SESSION['session_token'] ?? '');

if (!$user || $user['user_type'] !== 'tenant') {
    die("Invalid session");
}

echo "<h1>Testing Messaging API</h1>";
echo "<p><strong>User ID:</strong> " . $user['user_id'] . "</p>";
echo "<p><strong>User Type:</strong> " . $user['user_type'] . "</p>";
echo "<p><strong>Session Token:</strong> " . ($_SESSION['session_token'] ?? 'None') . "</p>";

// Test database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

echo "<p><strong>Database:</strong> Connected successfully</p>";

// Test getting available homeowners
$query = "SELECT id, first_name, last_name FROM homeowners LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Available Homeowners:</h2>";
foreach ($homeowners as $homeowner) {
    echo "<p>ID: " . $homeowner['id'] . " - " . $homeowner['first_name'] . " " . $homeowner['last_name'] . "</p>";
}

// Test getting available homes
$query = "SELECT h.id, hd.home_name, h.homeowner_id 
          FROM home h 
          JOIN home_details hd ON h.id = hd.home_id 
          WHERE hd.is_available = 1 
          LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$homes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Available Homes:</h2>";
foreach ($homes as $home) {
    echo "<p>Home ID: " . $home['id'] . " - " . $home['home_name'] . " (Homeowner: " . $home['homeowner_id'] . ")</p>";
}

// Test Messaging class
$messaging = new Messaging();

echo "<h2>Testing Messaging Class:</h2>";

// Test creating a conversation
if (!empty($homeowners) && !empty($homes)) {
    $homeowner_id = $homeowners[0]['id'];
    $home_id = $homes[0]['id'];
    
    echo "<p>Testing conversation creation with homeowner ID: " . $homeowner_id . " and home ID: " . $home_id . "</p>";
    
    $result = $messaging->getOrCreateConversation($user['user_id'], $homeowner_id, $home_id);
    
    if ($result['success']) {
        echo "<p><strong>Conversation created successfully!</strong> ID: " . $result['conversation_id'] . "</p>";
        
        // Test sending a message
        $message_result = $messaging->sendMessage(
            $result['conversation_id'],
            $user['user_id'],
            'tenant',
            $homeowner_id,
            'homeowner',
            'This is a test message from the API test'
        );
        
        if ($message_result['success']) {
            echo "<p><strong>Message sent successfully!</strong> Message ID: " . $message_result['message_id'] . "</p>";
        } else {
            echo "<p><strong>Message failed:</strong> " . $message_result['message'] . "</p>";
        }
        
        // Test getting messages
        $messages = $messaging->getMessages($result['conversation_id']);
        echo "<p><strong>Messages in conversation:</strong> " . count($messages) . "</p>";
        
        foreach ($messages as $message) {
            echo "<p>Message: " . htmlspecialchars($message['content']) . " (Sent by: " . $message['sender_type'] . ")</p>";
        }
        
    } else {
        echo "<p><strong>Conversation creation failed:</strong> " . $result['message'] . "</p>";
    }
} else {
    echo "<p>No homeowners or homes available for testing</p>";
}

// Test getting conversations
echo "<h2>User Conversations:</h2>";
$conversations = $messaging->getConversations($user['user_id'], $user['user_type']);
echo "<p><strong>Total conversations:</strong> " . count($conversations) . "</p>";

foreach ($conversations as $conv) {
    echo "<p>Conversation ID: " . $conv['id'] . " - " . $conv['other_user_name'] . "</p>";
}

echo "<hr>";
echo "<p><a href='tenant-dashboard.php'>Back to Tenant Dashboard</a></p>";
echo "<p><a href='messages.php'>Go to Messages</a></p>";
?>
