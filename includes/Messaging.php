<?php
require_once __DIR__ . '/../config/database.php';

class Messaging {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create or get existing conversation between tenant and homeowner
    public function getOrCreateConversation($tenant_id, $homeowner_id, $home_id = null) {
        // Check if conversation already exists
        $query = "SELECT id FROM conversations 
                  WHERE tenant_id = :tenant_id 
                  AND homeowner_id = :homeowner_id 
                  AND (home_id = :home_id OR (home_id IS NULL AND :home_id IS NULL))";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tenant_id", $tenant_id);
        $stmt->bindParam(":homeowner_id", $homeowner_id);
        $stmt->bindParam(":home_id", $home_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'conversation_id' => $row['id']];
        }

        // Create new conversation
        $query = "INSERT INTO conversations (tenant_id, homeowner_id, home_id) 
                  VALUES (:tenant_id, :homeowner_id, :home_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tenant_id", $tenant_id);
        $stmt->bindParam(":homeowner_id", $homeowner_id);
        $stmt->bindParam(":home_id", $home_id);

        if ($stmt->execute()) {
            $conversation_id = $this->conn->lastInsertId();
            return ['success' => true, 'conversation_id' => $conversation_id];
        }

        return ['success' => false, 'message' => 'Failed to create conversation'];
    }

    // Send a message
    public function sendMessage($conversation_id, $sender_id, $sender_type, $receiver_id, $receiver_type, $content) {
        // Sanitize content
        $content = htmlspecialchars(strip_tags($content));
        
        if (empty($content)) {
            return ['success' => false, 'message' => 'Message content cannot be empty'];
        }

        // Insert message
        $query = "INSERT INTO messages (conversation_id, sender_id, sender_type, receiver_id, receiver_type, content) 
                  VALUES (:conversation_id, :sender_id, :sender_type, :receiver_id, :receiver_type, :content)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":sender_id", $sender_id);
        $stmt->bindParam(":sender_type", $sender_type);
        $stmt->bindParam(":receiver_id", $receiver_id);
        $stmt->bindParam(":receiver_type", $receiver_type);
        $stmt->bindParam(":content", $content);

        if ($stmt->execute()) {
            // Update conversation's last_message_at
            $this->updateConversationTimestamp($conversation_id);
            return ['success' => true, 'message_id' => $this->conn->lastInsertId()];
        }

        return ['success' => false, 'message' => 'Failed to send message'];
    }

    // Get messages for a conversation
    public function getMessages($conversation_id, $limit = 50, $offset = 0) {
        $query = "SELECT m.*, 
                         CASE 
                             WHEN m.sender_type = 'tenant' THEN CONCAT(t.first_name, ' ', t.last_name)
                             WHEN m.sender_type = 'homeowner' THEN CONCAT(h.first_name, ' ', h.last_name)
                         END as sender_name
                  FROM messages m
                  LEFT JOIN tenants t ON m.sender_type = 'tenant' AND m.sender_id = t.id
                  LEFT JOIN homeowners h ON m.sender_type = 'homeowner' AND m.sender_id = h.id
                  WHERE m.conversation_id = :conversation_id
                  ORDER BY m.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get conversations for a user
    public function getConversations($user_id, $user_type) {
        if ($user_type === 'tenant') {
            $query = "SELECT c.*, 
                             CONCAT(h.first_name, ' ', h.last_name) as other_user_name,
                             h.email as other_user_email,
                             hd.home_name,
                             hd.address,
                             hd.city,
                             (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.receiver_id = :user_id AND m.receiver_type = :user_type) as unread_count,
                             (SELECT m.content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message
                      FROM conversations c
                      JOIN homeowners h ON c.homeowner_id = h.id
                      LEFT JOIN home_details hd ON c.home_id = hd.home_id
                      WHERE c.tenant_id = :user_id
                      ORDER BY c.last_message_at DESC";
        } else {
            $query = "SELECT c.*, 
                             CONCAT(t.first_name, ' ', t.last_name) as other_user_name,
                             t.email as other_user_email,
                             hd.home_name,
                             hd.address,
                             hd.city,
                             (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.receiver_id = :user_id AND m.receiver_type = :user_type) as unread_count,
                             (SELECT m.content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message
                      FROM conversations c
                      JOIN tenants t ON c.tenant_id = t.id
                      LEFT JOIN home_details hd ON c.home_id = hd.home_id
                      WHERE c.homeowner_id = :user_id
                      ORDER BY c.last_message_at DESC";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":user_type", $user_type);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mark messages as read
    public function markMessagesAsRead($conversation_id, $user_id, $user_type) {
        $query = "UPDATE messages 
                  SET is_read = 1 
                  WHERE conversation_id = :conversation_id 
                  AND receiver_id = :user_id 
                  AND receiver_type = :user_type 
                  AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":user_type", $user_type);
        
        return $stmt->execute();
    }

    // Get unread message count for a user
    public function getUnreadCount($user_id, $user_type) {
        $query = "SELECT COUNT(*) as count 
                  FROM messages m
                  JOIN conversations c ON m.conversation_id = c.id
                  WHERE m.receiver_id = :user_id 
                  AND m.receiver_type = :user_type 
                  AND m.is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":user_type", $user_type);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // Update conversation timestamp
    private function updateConversationTimestamp($conversation_id) {
        $query = "UPDATE conversations 
                  SET last_message_at = CURRENT_TIMESTAMP 
                  WHERE id = :conversation_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->execute();
    }

    // Get conversation details
    public function getConversationDetails($conversation_id) {
        $query = "SELECT c.*, 
                         CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
                         t.email as tenant_email,
                         CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
                         h.email as homeowner_email,
                         hd.home_name,
                         hd.address,
                         hd.city
                  FROM conversations c
                  JOIN tenants t ON c.tenant_id = t.id
                  JOIN homeowners h ON c.homeowner_id = h.id
                  LEFT JOIN home_details hd ON c.home_id = hd.home_id
                  WHERE c.id = :conversation_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 