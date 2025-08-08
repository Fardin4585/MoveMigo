<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/Auth.php';
require_once '../includes/Messaging.php';

$auth = new Auth();
$messaging = new Messaging();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);

// Get authorization header
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Extract token from Authorization header (Bearer token)
$token = '';
if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $token = $matches[1];
}

// Validate session
$user = $auth->validateSession($token);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            // Get conversations or messages
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'conversations':
                        // Get all conversations for the user
                        $conversations = $messaging->getConversations($user['user_id'], $user['user_type']);
                        echo json_encode(['success' => true, 'conversations' => $conversations]);
                        break;

                    case 'messages':
                        // Get messages for a specific conversation
                        if (!isset($_GET['conversation_id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Conversation ID is required']);
                            exit();
                        }
                        
                        $conversation_id = (int)$_GET['conversation_id'];
                        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
                        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                        
                        // Mark messages as read when user views them
                        $messaging->markMessagesAsRead($conversation_id, $user['user_id'], $user['user_type']);
                        
                        $messages = $messaging->getMessages($conversation_id, $limit, $offset);
                        echo json_encode(['success' => true, 'messages' => $messages]);
                        break;

                    case 'unread_count':
                        // Get unread message count
                        $unread_count = $messaging->getUnreadCount($user['user_id'], $user['user_type']);
                        echo json_encode(['success' => true, 'unread_count' => $unread_count]);
                        break;

                    case 'conversation_details':
                        // Get conversation details
                        if (!isset($_GET['conversation_id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Conversation ID is required']);
                            exit();
                        }
                        
                        $conversation_id = (int)$_GET['conversation_id'];
                        $details = $messaging->getConversationDetails($conversation_id);
                        
                        if ($details) {
                            echo json_encode(['success' => true, 'conversation' => $details]);
                        } else {
                            http_response_code(404);
                            echo json_encode(['error' => 'Conversation not found']);
                        }
                        break;

                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action parameter is required']);
            }
            break;

        case 'POST':
            // Send a message or create conversation
            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'send_message':
                        // Send a message
                        if (!isset($input['receiver_id']) || !isset($input['receiver_type']) || !isset($input['content'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Receiver ID, receiver type, and content are required']);
                            exit();
                        }

                        $receiver_id = (int)$input['receiver_id'];
                        $receiver_type = $input['receiver_type'];
                        $content = $input['content'];
                        $home_id = isset($input['home_id']) ? (int)$input['home_id'] : null;

                        // Validate receiver type
                        if (!in_array($receiver_type, ['tenant', 'homeowner'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Invalid receiver type']);
                            exit();
                        }

                        // Ensure sender and receiver are different types
                        if ($user['user_type'] === $receiver_type) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Cannot send message to same user type']);
                            exit();
                        }

                        // Determine tenant and homeowner IDs
                        if ($user['user_type'] === 'tenant') {
                            $tenant_id = $user['user_id'];
                            $homeowner_id = $receiver_id;
                        } else {
                            $tenant_id = $receiver_id;
                            $homeowner_id = $user['user_id'];
                        }

                        // Get or create conversation
                        $conversation_result = $messaging->getOrCreateConversation($tenant_id, $homeowner_id, $home_id);
                        
                        if (!$conversation_result['success']) {
                            http_response_code(500);
                            echo json_encode(['error' => $conversation_result['message']]);
                            exit();
                        }

                        $conversation_id = $conversation_result['conversation_id'];

                        // Send the message
                        $result = $messaging->sendMessage(
                            $conversation_id,
                            $user['user_id'],
                            $user['user_type'],
                            $receiver_id,
                            $receiver_type,
                            $content
                        );

                        if ($result['success']) {
                            echo json_encode([
                                'success' => true, 
                                'message' => 'Message sent successfully',
                                'message_id' => $result['message_id'],
                                'conversation_id' => $conversation_id
                            ]);
                        } else {
                            http_response_code(500);
                            echo json_encode(['error' => $result['message']]);
                        }
                        break;

                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action parameter is required']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?> 