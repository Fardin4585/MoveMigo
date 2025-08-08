<?php
require_once 'config/database.php';
require_once 'includes/HomeManager.php';

echo "<h2>Testing Tenant Dashboard Database Connection</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test HomeManager
        $homeManager = new HomeManager();
        echo "<p style='color: green;'>✓ HomeManager loaded successfully</p>";
        
        // Test getting available homes
        $available_homes = $homeManager->getAllAvailableHomes();
        echo "<p style='color: green;'>✓ Retrieved " . count($available_homes) . " available homes</p>";
        
        if (count($available_homes) > 0) {
            echo "<h3>Available Properties:</h3>";
            foreach ($available_homes as $home) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>Property ID:</strong> " . $home['home_id'] . "<br>";
                echo "<strong>Name:</strong> " . htmlspecialchars($home['home_name']) . "<br>";
                echo "<strong>Bedrooms:</strong> " . $home['num_of_bedrooms'] . "<br>";
                echo "<strong>Washrooms:</strong> " . $home['washrooms'] . "<br>";
                echo "<strong>Rent:</strong> $" . number_format($home['rent_monthly']) . "/mo<br>";
                echo "<strong>Utilities:</strong> $" . number_format($home['utility_bills']) . "/mo<br>";
                echo "<strong>Facilities:</strong> " . ($home['facilities'] ?: 'None') . "<br>";
                echo "<strong>Tenant Type:</strong> " . ucfirst($home['family_bachelor_status']) . "<br>";
                echo "<strong>Location:</strong> " . htmlspecialchars($home['address'] . ', ' . $home['city']) . "<br>";
                echo "<strong>Homeowner:</strong> " . htmlspecialchars($home['homeowner_first_name'] . ' ' . $home['homeowner_last_name']) . "<br>";
                echo "<strong>Contact:</strong> " . htmlspecialchars($home['homeowner_phone']) . "<br>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ No available homes found. Add some properties in the homeowner dashboard first.</p>";
        }
        
        // Test database tables
        echo "<h3>Database Tables Check:</h3>";
        $tables = ['home', 'home_details', 'homeowners', 'tenants'];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
            }
        }
        
        echo "<h3>Next Steps:</h3>";
        echo "<p><a href='signin.php'>Go to Sign In</a></p>";
        echo "<p><a href='homeowner-dashboard.php'>Go to Homeowner Dashboard</a></p>";
        echo "<p><a href='tenant-dashboard.php'>Go to Tenant Dashboard</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 