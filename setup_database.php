<?php
require_once 'config/database.php';

echo "<h2>Setting up MoveMigo Database</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Drop existing tables if they exist (in reverse order of dependencies)
        $tables_to_drop = [
            'user_sessions',
            'property_images', 
            'properties',
            'home_details',
            'home',
            'homeowner_profiles',
            'tenant_profiles',
            'users',
            'homeowners',
            'tenants'
        ];
        
        echo "<h3>Dropping old tables...</h3>";
        foreach ($tables_to_drop as $table) {
            try {
                $conn->exec("DROP TABLE IF EXISTS `$table`");
                echo "<p style='color: orange;'>- Dropped table '$table' (if existed)</p>";
            } catch (Exception $e) {
                echo "<p style='color: gray;'>- Table '$table' didn't exist</p>";
            }
        }
        
        echo "<h3>Creating new tables...</h3>";
        
        // Create tenants table
        $sql = "CREATE TABLE tenants (
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
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Created tenants table</p>";
        
        // Create homeowners table
        $sql = "CREATE TABLE homeowners (
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
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Created homeowners table</p>";
        
        // Create home table (owned by homeowners)
        $sql = "CREATE TABLE home (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Created home table</p>";
        
        // Create home_details table
        $sql = "CREATE TABLE home_details (
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
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Created home_details table</p>";
        
        // Create property images table
        $sql = "CREATE TABLE property_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            home_id INT NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (home_id) REFERENCES home(id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Created property_images table</p>";
        
        // Create user_sessions table
        $sql = "CREATE TABLE user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_type ENUM('tenant', 'homeowner') NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Created user_sessions table</p>";
        
        // Create indexes
        echo "<h3>Creating indexes...</h3>";
        $indexes = [
            "CREATE INDEX idx_tenants_email ON tenants(email)",
            "CREATE INDEX idx_homeowners_email ON homeowners(email)",
            "CREATE INDEX idx_home_homeowner ON home(homeowner_id)",
            "CREATE INDEX idx_home_details_home ON home_details(home_id)",
            "CREATE INDEX idx_home_details_available ON home_details(is_available)",
            "CREATE INDEX idx_property_images_home ON property_images(home_id)",
            "CREATE INDEX idx_sessions_token ON user_sessions(session_token)",
            "CREATE INDEX idx_sessions_expires ON user_sessions(expires_at)"
        ];
        
        foreach ($indexes as $index) {
            $conn->exec($index);
        }
        echo "<p style='color: green;'>✓ Created all indexes</p>";
        
        echo "<h3>Database setup completed successfully!</h3>";
        echo "<p style='color: green;'>✓ All tables created with new structure</p>";
        echo "<p style='color: green;'>✓ Indexes created for better performance</p>";
        echo "<p><a href='test-database.php'>Test the database</a></p>";
        echo "<p><a href='signin.php'>Go to sign in page</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 