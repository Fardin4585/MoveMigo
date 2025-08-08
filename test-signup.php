<?php
// Simple test page to verify signup functionality
echo "<h1>MoveMigo Signup Test</h1>";
echo "<p>This page tests the signup functionality.</p>";

// Test database connection
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test Auth class
try {
    require_once 'includes/Auth.php';
    $auth = new Auth();
    echo "<p style='color: green;'>✅ Auth class loaded successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Auth class failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='signin.php'>Sign In Page</a></li>";
echo "<li><a href='signup-tenant.php'>Tenant Signup</a></li>";
echo "<li><a href='signup-homeowner.php'>Homeowner Signup</a></li>";
echo "</ul>";

echo "<h2>Sample Test Accounts:</h2>";
echo "<p><strong>Tenant:</strong> tenant@example.com / password</p>";
echo "<p><strong>Homeowner:</strong> homeowner@example.com / password</p>";
?> 