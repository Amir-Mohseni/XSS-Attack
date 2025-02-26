<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check cookie authentication
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_session'])) {
    $user_id = (int)$_COOKIE['user_session'];
    
    // Verify user exists with prepared statement
    $query = "SELECT * FROM users WHERE id = ?";
    $result = db_query($query, "i", [$user_id]);
    
    if (!empty($result)) {
        $user = $result[0];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Debug output
        error_log("Debug - Cookie Authentication in comments.php - User ID: " . $user['id']);
    }
}

if (!isset($_SESSION['user_id'])) {
    die('Not logged in. <a href="index.php">Return to homepage</a>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['content']) || trim($_POST['content']) === '') {
        die('Comment content is required');
    }

    $content = $_POST['content'];
    $user_id = (int)$_SESSION['user_id']; // Cast to integer
    
    // Debug output
    error_log("Debug - User ID: $user_id");
    error_log("Debug - Content: $content");
    
    // Verify user exists with prepared statement
    $check_query = "SELECT id FROM users WHERE id = ?";
    $check_result = db_query($check_query, "i", [$user_id]);
    
    if (empty($check_result)) {
        die("Error: Invalid user ID. Please log in again.");
    }
    
    // Insert comment with prepared statement
    $query = "INSERT INTO comments (user_id, content) VALUES (?, ?)";
    $result = db_query($query, "is", [$user_id, $content]);
    
    if ($result === false) {
        die('Error saving comment. Please try again.');
    }
    
    header('Location: index.php');
    exit();
} 