<?php
session_start();

// 1. Connect to Database safely
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create and select database
$conn->query("CREATE DATABASE IF NOT EXISTS student_management");
$conn->select_db("student_management");

// 3. Ensure users table exists
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    username VARCHAR(50) UNIQUE, 
    password VARCHAR(255), 
    role VARCHAR(20)
)");

// 4. Ensure students table exists
$conn->query("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY
)");

// 5. Safely add ALL missing columns for the "Read All Data" table
// We check if the column exists before adding it to prevent errors
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

// Add columns to students table
addColumnIfNotExists($conn, 'students', 'name', 'VARCHAR(255) DEFAULT NULL');
addColumnIfNotExists($conn, 'students', 'email', 'VARCHAR(255) DEFAULT NULL');
addColumnIfNotExists($conn, 'students', 'phone', 'VARCHAR(50) DEFAULT NULL');
addColumnIfNotExists($conn, 'students', 'date_of_birth', 'DATE DEFAULT NULL');
addColumnIfNotExists($conn, 'students', 'grade', 'VARCHAR(50) DEFAULT NULL');
addColumnIfNotExists($conn, 'students', 'status', "ENUM('Active','Inactive','Graduated') DEFAULT 'Active'");

// 6. AUTO-FIX ADMIN LOGIN (Ensures you can always login)
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$res = $conn->query("SELECT id FROM users WHERE username='admin'");

if ($res->num_rows > 0) {
    // Update password if admin exists (in case it was broken)
    $conn->query("UPDATE users SET password='$hash', role='admin' WHERE username='admin'");
} else {
    // Create admin if it doesn't exist
    $conn->query("INSERT INTO users (username, password, role) VALUES ('admin', '$hash', 'admin')");
}
?>