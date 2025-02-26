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

    // Create default admin user with hashed password
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $admin_username, $admin_password);
    $result = $stmt->execute();
    $stmt->close();
    
    error_log("Debug - Admin user created: " . ($result ? "Yes" : "No"));
    error_log("Debug - Database initialized with tables and admin user");
}

/**
 * Helper function to execute prepared statements
 * 
 * @param string $query The SQL query with placeholders
 * @param string $types The types of parameters (s: string, i: integer, d: double, b: blob)
 * @param array $params The parameters to bind
 * @return array|bool Array of results or boolean for non-select queries
 */
function db_query($query, $types = "", $params = []) {
    global $mysqli;
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $bind_params = [$types];
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    // For SELECT queries
    $result = $stmt->get_result();
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }
    
    // For INSERT, UPDATE, DELETE queries
    $affected_rows = $stmt->affected_rows;
    $insert_id = $stmt->insert_id;
    $stmt->close();
    
    return [
        'affected_rows' => $affected_rows,
        'insert_id' => $insert_id
    ];
} 