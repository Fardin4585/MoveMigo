<?php
require_once 'config/database.php';

class Auth {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Register a new user
    public function register($email, $password, $user_type, $first_name, $last_name, $phone = null, $additional_data = []) {
        // Check if user already exists in either table
        if ($this->userExists($email, $user_type)) {
            return ['success' => false, 'message' => 'User with this email already exists'];
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Sanitize inputs
        $email = htmlspecialchars(strip_tags($email));
        $first_name = htmlspecialchars(strip_tags($first_name));
        $last_name = htmlspecialchars(strip_tags($last_name));
        $phone = $phone ? htmlspecialchars(strip_tags($phone)) : null;

        if ($user_type === 'tenant') {
            return $this->registerTenant($email, $password_hash, $first_name, $last_name, $phone);
        } else {
            return $this->registerHomeowner($email, $password_hash, $first_name, $last_name, $phone, $additional_data);
        }
    }

    // Register tenant
    private function registerTenant($email, $password_hash, $first_name, $last_name, $phone) {
        $query = "INSERT INTO tenants (email, password_hash, first_name, last_name, phone) 
                  VALUES (:email, :password_hash, :first_name, :last_name, :phone)";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);
        $stmt->bindParam(":phone", $phone);

        if ($stmt->execute()) {
            $user_id = $this->conn->lastInsertId();
            return ['success' => true, 'message' => 'Tenant registered successfully', 'user_id' => $user_id];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    // Register homeowner
    private function registerHomeowner($email, $password_hash, $first_name, $last_name, $phone, $additional_data = []) {
        $years_experience = $additional_data['years_experience'] ?? null;

        $query = "INSERT INTO homeowners (email, password_hash, first_name, last_name, phone, years_experience) 
                  VALUES (:email, :password_hash, :first_name, :last_name, :phone, :years_experience)";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":years_experience", $years_experience);

        if ($stmt->execute()) {
            $user_id = $this->conn->lastInsertId();
            return ['success' => true, 'message' => 'Homeowner registered successfully', 'user_id' => $user_id];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    // Login user
    public function login($email, $password) {
        // Try to find user in tenants table
        $query = "SELECT id, email, password_hash, first_name, last_name, is_active 
                  FROM tenants 
                  WHERE email = :email AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                // Create session
                $session_token = $this->createSession($row['id'], 'tenant');
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $row['id'],
                        'email' => $row['email'],
                        'user_type' => 'tenant',
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name']
                    ],
                    'session_token' => $session_token
                ];
            }
        }

        // Try to find user in homeowners table
        $query = "SELECT id, email, password_hash, first_name, last_name, is_active 
                  FROM homeowners 
                  WHERE email = :email AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                // Create session
                $session_token = $this->createSession($row['id'], 'homeowner');
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $row['id'],
                        'email' => $row['email'],
                        'user_type' => 'homeowner',
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name']
                    ],
                    'session_token' => $session_token
                ];
            }
        }

        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    // Check if user exists
    private function userExists($email, $user_type) {
        if ($user_type === 'tenant') {
            $query = "SELECT id FROM tenants WHERE email = :email";
        } else {
            $query = "SELECT id FROM homeowners WHERE email = :email";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Create session
    private function createSession($user_id, $user_type) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $query = "INSERT INTO user_sessions (user_id, user_type, session_token, expires_at) 
                  VALUES (:user_id, :user_type, :token, :expires_at)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":user_type", $user_type);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expires_at);
        $stmt->execute();

        return $token;
    }

    // Validate session
    public function validateSession($token) {
        $query = "SELECT us.user_id, us.user_type, us.session_token, us.expires_at
                  FROM user_sessions us 
                  WHERE us.session_token = :token AND us.expires_at > NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get user details based on user type
            if ($session['user_type'] === 'tenant') {
                $query = "SELECT id, email, first_name, last_name FROM tenants WHERE id = :id";
            } else {
                $query = "SELECT id, email, first_name, last_name FROM homeowners WHERE id = :id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $session['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'user_type' => $session['user_type'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ];
            }
        }
        return false;
    }

    // Logout user
    public function logout($token) {
        $query = "DELETE FROM user_sessions WHERE session_token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        return $stmt->execute();
    }

    // Clean expired sessions
    public function cleanExpiredSessions() {
        $query = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }
}
?> 