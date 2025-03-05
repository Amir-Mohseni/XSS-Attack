<?php
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow CORS for the attacker server
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get user ID from query parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    // Query to get username by ID
    $query = "SELECT username FROM users WHERE id = $user_id";
    $result = $mysqli->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Return username in a simple XML format
        echo "<username>" . htmlspecialchars($user['username']) . "</username>";
    } else {
        echo "<username>unknown</username>";
    }
} else {
    echo "<username>invalid_id</username>";
}
?> 