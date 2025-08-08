# MoveMigo Messaging Feature

This document describes the messaging feature implementation for the MoveMigo platform, allowing tenants and homeowners to communicate with each other.

## Database Schema

### Conversations Table
```sql
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    home_id INT,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE,
    FOREIGN KEY (home_id) REFERENCES home(id) ON DELETE SET NULL,
    UNIQUE KEY unique_conversation (tenant_id, homeowner_id, home_id)
);
```

### Messages Table
```sql
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('tenant', 'homeowner') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_type ENUM('tenant', 'homeowner') NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);
```

## Files Created/Modified

### New Files
1. **`includes/Messaging.php`** - Core messaging functionality class
2. **`api/messaging.php`** - REST API endpoints for messaging
3. **`api/get-homeowner-info.php`** - API to get homeowner information
4. **`messages.php`** - Main messaging interface page
5. **`test-messaging.php`** - Test page for debugging messaging functionality

### Modified Files
1. **`database_setup.sql`** - Added messaging tables and indexes
2. **`tenant-dashboard.php`** - Added messaging buttons and navigation
3. **`homeowner-dashboard.php`** - Added messaging navigation

## Features Implemented

### 1. Conversation Management
- Create conversations between tenants and homeowners
- Support for property-specific conversations
- Automatic conversation creation when first message is sent

### 2. Message System
- Send and receive messages
- Real-time message display
- Message read status tracking
- Unread message count

### 3. User Interface
- Modern chat interface with conversation list
- Message bubbles with timestamps
- Unread message indicators
- Responsive design for mobile devices

### 4. API Endpoints

#### GET `/api/messaging.php`
- `action=conversations` - Get user's conversations
- `action=messages&conversation_id=X` - Get messages for a conversation
- `action=unread_count` - Get unread message count
- `action=conversation_details&conversation_id=X` - Get conversation details

#### POST `/api/messaging.php`
- `action=send_message` - Send a new message

#### GET `/api/get-homeowner-info.php`
- `home_id=X` - Get homeowner information for a specific property

## Usage

### For Tenants
1. Browse available properties on the tenant dashboard
2. Click "Message" button on any property card
3. Access all conversations via the "Messages" link in the profile dropdown
4. Send messages to homeowners about properties

### For Homeowners
1. Access conversations via the "Messages" link in the profile dropdown
2. View and respond to messages from tenants
3. See property-specific conversations

### API Authentication
All API endpoints require authentication using Bearer tokens:
```
Authorization: Bearer <session_token>
```

## Security Features

1. **Input Sanitization** - All message content is sanitized to prevent XSS
2. **Authentication** - All endpoints require valid session tokens
3. **Authorization** - Users can only access their own conversations
4. **SQL Injection Protection** - All queries use prepared statements

## Testing

Use the `test-messaging.php` page to:
- View current conversations
- Test API endpoints
- Verify unread message counts
- Debug messaging functionality

## Future Enhancements

1. **Real-time Updates** - WebSocket integration for live messaging
2. **File Attachments** - Support for images and documents
3. **Message Notifications** - Email/SMS notifications for new messages
4. **Message Search** - Search functionality within conversations
5. **Message Encryption** - End-to-end encryption for privacy
6. **Message Templates** - Pre-written message templates for common inquiries

## Database Indexes

The following indexes are created for optimal performance:
- `idx_conversations_tenant` - Fast tenant conversation lookup
- `idx_conversations_homeowner` - Fast homeowner conversation lookup
- `idx_conversations_home` - Fast property-specific conversation lookup
- `idx_conversations_last_message` - Sort conversations by activity
- `idx_messages_conversation` - Fast message retrieval
- `idx_messages_sender` - Fast sender-based queries
- `idx_messages_receiver` - Fast receiver-based queries
- `idx_messages_created_at` - Sort messages by time
- `idx_messages_unread` - Fast unread message queries

## Error Handling

The messaging system includes comprehensive error handling:
- Invalid session tokens return 401 Unauthorized
- Missing required parameters return 400 Bad Request
- Database errors return 500 Internal Server Error
- Not found resources return 404 Not Found

## Browser Compatibility

The messaging interface is compatible with:
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

Mobile responsiveness is included for iOS Safari and Chrome Mobile. 