<?php
// Database configuration for MoveMigo
class Database {
    private $host = 'localhost';
    private $db_name = 'movemigo_db';
    private $username = 'root';  // Change this to your MySQL username
    private $password = '';      // Change this to your MySQL password
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?> 