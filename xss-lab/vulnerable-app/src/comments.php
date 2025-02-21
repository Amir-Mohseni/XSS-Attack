<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['author']) || !isset($_POST['content'])) {
        die('Missing required fields');
    }

    $author = $_POST['author'];
    $content = $_POST['content'];
    
    // Vulnerable: No input sanitization (intentional for demonstration)
    $query = "INSERT INTO comments (author, content) VALUES ('$author', '$content')";
    
    if (!$mysqli->query($query)) {
        die('Error saving comment: ' . $mysqli->error);
    }
    
    header('Location: index.php');
    exit();
} 