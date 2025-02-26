<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$maxAttempts = 10;
$attempt = 1;
$connected = false;

while ($attempt <= $maxAttempts && !$connected) {
    try {
        $host = 'db';
        $dbname = 'vulnerable_db';
        $username = 'dbuser';
        $password = 'dbpassword';

        $mysqli = new mysqli($host, $username, $password, $dbname);

        if (!$mysqli->connect_error) {
            $connected = true;
            break;
        }
    } catch (Exception $e) {
        error_log("Attempt $attempt: Failed to connect to MySQL - " . $e->getMessage());
    }

    if ($attempt < $maxAttempts) {
        sleep(3); // Wait 3 seconds before retrying
    }
    $attempt++;
}

if (!$connected) {
    die("Failed to connect to MySQL after $maxAttempts attempts. Please try again later.");
}

// Check if tables need to be created (first run)
$check_tables = $mysqli->query("SHOW TABLES LIKE 'users'");
$tables_exist = ($check_tables && $check_tables->num_rows > 0);

if (!$tables_exist) {
    // Create users table first
    $query = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$mysqli->query($query)) {
        die("Error creating users table: " . $mysqli->error);
    }

    // Then create comments table with foreign key
    $query = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if (!$mysqli->query($query)) {
        die("Error creating comments table: " . $mysqli->error);
    }

    // Create default admin user
    $result = $mysqli->query("INSERT INTO users (username, password) VALUES ('admin', 'admin123')");
    error_log("Debug - Admin user created: " . ($result ? "Yes" : "No"));
    
    error_log("Debug - Database initialized with tables and admin user");
} 