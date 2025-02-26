<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in via session or cookie
$logged_in = isset($_SESSION['user_id']);

// If not logged in but has a cookie, try to authenticate via cookie
if (!$logged_in && isset($_COOKIE['user_session'])) {
    $user_id = (int)$_COOKIE['user_session'];
    
    // Verify user exists with prepared statement
    $query = "SELECT * FROM users WHERE id = ?";
    $result = db_query($query, "i", [$user_id]);
    
    if (!empty($result)) {
        $user = $result[0];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $logged_in = true;
        
        // Debug output
        error_log("Debug - Cookie Authentication - User ID: " . $user['id']);
    }
}

// Set secure cookie if logged in
if ($logged_in) {
    setcookie('user_session', $_SESSION['user_id'], [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure Comment System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if (!$logged_in): ?>
            <h1>Welcome to Our Comment System</h1>
            <p>Please login or signup to continue.</p>
            
            <div class="auth-links" style="margin-top: 30px; text-align: center;">
                <a href="login.php" class="auth-button">Login</a>
                <a href="signup.php" class="auth-button">Sign Up</a>
            </div>
        <?php else: ?>
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <div class="comment-form">
                <h2>Leave a Comment</h2>
                <form action="comments.php" method="POST">
                    <textarea name="content" placeholder="Your Comment" required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
            </div>

            <div class="comments">
                <h2>Comments</h2>
                <?php
                try {
                    // Get comments with prepared statement
                    $query = "SELECT c.*, u.username 
                              FROM comments c 
                              JOIN users u ON c.user_id = u.id 
                              ORDER BY c.created_at DESC";
                    $comments = db_query($query);
                    
                    if ($comments === false) {
                        echo "<p>Error loading comments. Please try again later.</p>";
                    } else if (empty($comments)) {
                        echo "<p>No comments yet.</p>";
                    } else {
                        foreach ($comments as $row) {
                            echo "<div class='comment'>";
                            echo "<h3>" . htmlspecialchars($row['username']) . "</h3>";
                            // Prevent XSS by escaping content
                            echo "<p>" . htmlspecialchars($row['content']) . "</p>";
                            echo "<small>Posted on " . htmlspecialchars($row['created_at']) . "</small>";
                            echo "</div>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 