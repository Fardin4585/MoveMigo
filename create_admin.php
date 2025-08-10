<?php
require_once 'config/database.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection(); // Now $pdo is available

// Admin details
$name = "Super Admin";
$email = "admin@gmail.com";
$password = "Admin123"; // Change to a strong password

// Hash the password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert into admins table
$stmt = $pdo->prepare("INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :password_hash)");
$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password_hash' => $passwordHash
]);

echo "Admin account created successfully!";
