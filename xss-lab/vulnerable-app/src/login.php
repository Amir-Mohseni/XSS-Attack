<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Use prepared statement to prevent SQL injection
    $query = "SELECT * FROM users WHERE username = ?";
    $result = db_query($query, "s", [$username]);
    
    if ($result === false) {
        $error = "Database error. Please try again later.";
    } else if (empty($result)) {
        $error = "Invalid username or password";
    } else {
        $user = $result[0];
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Debug output
            error_log("Debug - User data: " . print_r($user, true));
            
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Set secure cookie
            setcookie('user_session', $_SESSION['user_id'], [
                'expires' => time() + 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // Debug output
            error_log("Debug - Session after login: " . print_r($_SESSION, true));
            
            header('Location: index.php');
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Secure App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Login</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
            
            <!-- Hint for SQL Injection -->
            <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <small>Try these SQL injection payloads:</small>
                <ul style="margin-top: 5px; font-size: 12px;">
                    <li><code>admin'-- -</code> (note the space after --)</li>
                    <li><code>' OR 1=1-- -</code></li>
                    <li><code>' OR '1'='1</code></li>
                    <li>Or use default admin/admin123</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 