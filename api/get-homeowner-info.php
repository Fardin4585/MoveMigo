<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/Auth.php';
require_once '../config/database.php';

$auth = new Auth();
$database = new Database();
$conn = $database->getConnection();

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
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['home_id'])) {
            $home_id = (int)$_GET['home_id'];
            
            // Get homeowner information for a specific home
            $query = "SELECT h.id as homeowner_id, 
                             h.first_name, 
                             h.last_name, 
                             h.email,
                             hd.home_name,
                             hd.address,
                             hd.city
                      FROM home ho
                      JOIN homeowners h ON ho.homeowner_id = h.id
                      LEFT JOIN home_details hd ON ho.id = hd.home_id
                      WHERE ho.id = :home_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(":home_id", $home_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'homeowner' => $homeowner]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Home not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Home ID is required']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?> 