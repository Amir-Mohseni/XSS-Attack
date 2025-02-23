<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check cookie authentication
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_session'])) {
    $user_id = (int)$_COOKIE['user_session'];
    
    // Verify user exists
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = $mysqli->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Debug output
        error_log("Debug - Cookie Authentication in comments.php - User ID: " . $user['id']);
    }
}

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['content'])) {
        die('Missing required fields');
    }

    $content = $_POST['content'];
    $user_id = (int)$_SESSION['user_id']; // Cast to integer
    
    // Debug output
    error_log("Debug - User ID: $user_id");
    error_log("Debug - Session: " . print_r($_SESSION, true));
    
    // Verify user exists
    $check_user = $mysqli->query("SELECT id FROM users WHERE id = $user_id");
    if (!$check_user || $check_user->num_rows === 0) {
        die("Error: Invalid user ID. Please log in again.");
    }
    
    // Escape the content string but keep it vulnerable to XSS
    $content = $mysqli->real_escape_string($content);
    
    // Vulnerable: SQL injection still possible through user_id (intentional)
    $query = "INSERT INTO comments (user_id, content) VALUES ($user_id, '$content')";
    error_log("Debug - SQL Query: $query");
    
    if (!$mysqli->query($query)) {
        die('Error saving comment: ' . $mysqli->error . 
            '<br>Query: ' . $query . 
            '<br>User ID: ' . $user_id);
    }
    
    header('Location: index.php');
    exit();
} 