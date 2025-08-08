<?php
require_once 'config/database.php';
require_once 'includes/Auth.php';

echo "<h2>Testing MoveMigo Database Structure</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test Auth class
        $auth = new Auth();
        echo "<p style='color: green;'>✓ Auth class loaded successfully</p>";
        
        // Test tenant registration
        $test_email = 'test_tenant_' . time() . '@example.com';
        $result = $auth->register($test_email, 'password123', 'tenant', 'Test', 'Tenant', '555-1234');
        
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Tenant registration successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Tenant registration failed: " . $result['message'] . "</p>";
        }
        
        // Test homeowner registration
        $test_email2 = 'test_homeowner_' . time() . '@example.com';
        $additional_data = [
            'company_name' => 'Test Properties',
            'business_license' => 'BL123456',
            'years_experience' => 5
        ];
        $result2 = $auth->register($test_email2, 'password123', 'homeowner', 'Test', 'Homeowner', '555-5678', $additional_data);
        
        if ($result2['success']) {
            echo "<p style='color: green;'>✓ Homeowner registration successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Homeowner registration failed: " . $result2['message'] . "</p>";
        }
        
        // Test login
        $login_result = $auth->login($test_email, 'password123');
        if ($login_result['success']) {
            echo "<p style='color: green;'>✓ Tenant login successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Tenant login failed: " . $login_result['message'] . "</p>";
        }
        
        $login_result2 = $auth->login($test_email2, 'password123');
        if ($login_result2['success']) {
            echo "<p style='color: green;'>✓ Homeowner login successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Homeowner login failed: " . $login_result2['message'] . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Database Tables Check</h3>";
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if tables exist
    $tables = ['tenants', 'homeowners', 'properties', 'property_images', 'user_sessions'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking tables: " . $e->getMessage() . "</p>";
}
?> 