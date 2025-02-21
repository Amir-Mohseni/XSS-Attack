<?php
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

// Create comments table if it doesn't exist
$query = "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$mysqli->query($query)) {
    die("Error creating table: " . $mysqli->error);
} 