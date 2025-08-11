<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header('Location: signin.php');
    exit();
}

$auth = new Auth();
$user = $auth->validateSession($_SESSION['session_token'] ?? '');

if (!$user || $user['user_type'] !== 'tenant') {
    session_destroy();
    header('Location: signin.php');
    exit();
}

// Database connection
$database = new Database();
$conn = $database->getConnection();

// Get one available home for testing
$query = "SELECT 
            h.id as home_id,
            hd.home_name,
            ho.id as homeowner_id,
            ho.first_name as homeowner_first_name,
            ho.last_name as homeowner_last_name
          FROM home h
          JOIN home_details hd ON h.id = hd.home_id
          JOIN homeowners ho ON h.homeowner_id = ho.id
          WHERE hd.is_available = 1
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->execute();
$test_home = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test_home) {
    die("No homes available for testing");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Message Button - MoveMigo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-card { border: 1px solid #ccc; padding: 20px; margin: 20px 0; }
        .btn-message { background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .debug-info { background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <h1>Test Message Button</h1>
    
    <div class="test-card">
        <h3>Test Property</h3>
        <p><strong>Home ID:</strong> <?php echo $test_home['home_id']; ?></p>
        <p><strong>Home Name:</strong> <?php echo htmlspecialchars($test_home['home_name']); ?></p>
        <p><strong>Homeowner ID:</strong> <?php echo $test_home['homeowner_id']; ?></p>
        <p><strong>Homeowner Name:</strong> <?php echo htmlspecialchars($test_home['homeowner_first_name'] . ' ' . $test_home['homeowner_last_name']); ?></p>
        
        <button class="btn-message" onclick="testMessageButton(<?php echo $test_home['home_id']; ?>, '<?php echo htmlspecialchars($test_home['homeowner_first_name'] . ' ' . $test_home['homeowner_last_name']); ?>')">
            Test Message Button
        </button>
    </div>
    
    <div class="debug-info">
        <h4>Debug Information:</h4>
        <p><strong>User ID:</strong> <?php echo $user['user_id']; ?></p>
        <p><strong>User Type:</strong> <?php echo $user['user_type']; ?></p>
        <p><strong>Session Token:</strong> <?php echo $_SESSION['session_token'] ?? 'None'; ?></p>
    </div>
    
    <div id="console-output" style="background: #000; color: #0f0; padding: 10px; margin: 10px 0; font-family: monospace; height: 200px; overflow-y: auto;"></div>
    
    <script>
        function log(message) {
            const console = document.getElementById('console-output');
            console.innerHTML += message + '\n';
            console.scrollTop = console.scrollHeight;
        }
        
        function testMessageButton(propertyId, homeownerName) {
            log('=== Testing Message Button ===');
            log('Property ID: ' + propertyId);
            log('Homeowner Name: ' + homeownerName);
            
            // Get homeowner ID from the property data
            const propertyCard = document.querySelector('.test-card');
            if (!propertyCard) {
                log('ERROR: Property card not found');
                return;
            }
            
            // Since we're in a test environment, we'll use the PHP variable
            const homeownerId = <?php echo $test_home['homeowner_id']; ?>;
            log('Homeowner ID: ' + homeownerId);
            
            if (!homeownerId) {
                log('ERROR: Homeowner ID not found');
                return;
            }
            
            // Store the property info in sessionStorage for the messages page
            sessionStorage.setItem('messagePropertyId', propertyId);
            sessionStorage.setItem('messageHomeownerId', homeownerId);
            sessionStorage.setItem('messageHomeownerName', homeownerName);
            
            log('Stored in sessionStorage:');
            log('  messagePropertyId: ' + sessionStorage.getItem('messagePropertyId'));
            log('  messageHomeownerId: ' + sessionStorage.getItem('messageHomeownerId'));
            log('  messageHomeownerName: ' + sessionStorage.getItem('messageHomeownerName'));
            
            log('Redirecting to messages.php...');
            
            // Redirect to messages page
            window.location.href = 'messages.php';
        }
        
        // Log when page loads
        log('Page loaded');
        log('Session storage contents:');
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            log('  ' + key + ': ' + sessionStorage.getItem(key));
        }
    </script>
</body>
</html>
