-- MoveMigo Database Setup
-- Run these commands in your MySQL database

-- Create the database
CREATE DATABASE IF NOT EXISTS movemigo_db;
USE movemigo_db;

-- Create tenants table
CREATE TABLE tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    preferred_location VARCHAR(255),
    move_in_date DATE,
    number_of_tenants INT DEFAULT 1,
    employment_status VARCHAR(50),
    annual_income DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Create homeowners table
CREATE TABLE homeowners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    years_experience INT,
    number_of_homes INT DEFAULT 0,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Create home table (owned by homeowners)
CREATE TABLE home (
    id INT AUTO_INCREMENT PRIMARY KEY,
    homeowner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE
);

-- Create home_details table
CREATE TABLE home_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    home_id INT NOT NULL,
    home_name VARCHAR(255) NOT NULL,
    num_of_bedrooms INT NOT NULL,
    washrooms INT NOT NULL,
    rent_monthly DECIMAL(10,2) NOT NULL,
    utility_bills DECIMAL(10,2) DEFAULT 0,
    facilities SET('wifi', 'water', 'gas', 'parking', 'furnished', 'AC') DEFAULT '',
    family_bachelor_status ENUM('family', 'bachelor', 'both') DEFAULT 'both',
    address VARCHAR(500),
    city VARCHAR(100),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    description TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (home_id) REFERENCES home(id) ON DELETE CASCADE
);

-- Create saved_properties table for tenant's saved homes
CREATE TABLE saved_properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    home_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (home_id) REFERENCES home(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_home (tenant_id, home_id)
);

-- Create property images table
CREATE TABLE property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    home_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (home_id) REFERENCES home(id) ON DELETE CASCADE
);

-- Create sessions table for user authentication
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('tenant', 'homeowner') NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create conversations table for messaging
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

-- Create messages table for individual messages
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

-- Create indexes for better performance
CREATE INDEX idx_tenants_email ON tenants(email);
CREATE INDEX idx_homeowners_email ON homeowners(email);
CREATE INDEX idx_home_homeowner ON home(homeowner_id);
CREATE INDEX idx_home_details_home ON home_details(home_id);
CREATE INDEX idx_home_details_available ON home_details(is_available);
CREATE INDEX idx_saved_properties_tenant ON saved_properties(tenant_id);
CREATE INDEX idx_saved_properties_home ON saved_properties(home_id);
CREATE INDEX idx_property_images_home ON property_images(home_id);
CREATE INDEX idx_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_conversations_tenant ON conversations(tenant_id);
CREATE INDEX idx_conversations_homeowner ON conversations(homeowner_id);
CREATE INDEX idx_conversations_home ON conversations(home_id);
CREATE INDEX idx_conversations_last_message ON conversations(last_message_at);
CREATE INDEX idx_messages_conversation ON messages(conversation_id);
CREATE INDEX idx_messages_sender ON messages(sender_id, sender_type);
CREATE INDEX idx_messages_receiver ON messages(receiver_id, receiver_type);
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_unread ON messages(is_read);

-- Insert sample data for testing (optional)
-- Sample homeowner
INSERT INTO homeowners (email, password_hash, first_name, last_name, phone, years_experience, number_of_homes) 
VALUES ('homeowner@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', '555-0123', 5, 0);

-- Sample tenant
INSERT INTO tenants (email, password_hash, first_name, last_name, phone, preferred_location) 
VALUES ('tenant@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Doe', '555-0456', 'Downtown');

-- Note: The password hash above is for 'password' - change this in production! 