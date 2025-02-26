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
    
    // Verify user exists
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = $mysqli->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $logged_in = true;
        
        // Debug output
        error_log("Debug - Cookie Authentication - User ID: " . $user['id']);
    }
}

// Set cookie if logged in
if ($logged_in) {
    setcookie('user_session', $_SESSION['user_id'], time() + 3600, '/', '', false, false);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vulnerable Comment System</title>
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
                    // Updated query to match schema and show all fields
                    $query = "SELECT c.*, u.username 
                              FROM comments c 
                              JOIN users u ON c.user_id = u.id 
                              ORDER BY c.created_at DESC";
                    $result = $mysqli->query($query);
                    
                    if ($result === false) {
                        echo "<p>Error loading comments: " . $mysqli->error . "</p>";
                    } else {
                        if ($result->num_rows == 0) {
                            echo "<p>No comments yet.</p>";
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                echo "<div class='comment'>";
                                echo "<h3>" . htmlspecialchars($row['username']) . "</h3>";
                                // Intentionally vulnerable to XSS
                                echo "<p>" . $row['content'] . "</p>";
                                echo "<small>Posted on " . $row['created_at'] . "</small>";
                                echo "</div>";
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo "<p>Error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 