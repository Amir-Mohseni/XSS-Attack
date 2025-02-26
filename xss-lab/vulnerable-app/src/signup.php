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
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if username exists using prepared statement
        $check_query = "SELECT * FROM users WHERE username = ?";
        $result = db_query($check_query, "s", [$username]);
        
        if ($result === false) {
            $error = "Database error: Please try again later";
        } else if (!empty($result)) {
            $error = "Username already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with prepared statement
            $query = "INSERT INTO users (username, password) VALUES (?, ?)";
            $insert_result = db_query($query, "ss", [$username, $hashed_password]);
            
            if ($insert_result === false) {
                $error = "Error creating account. Please try again.";
            } else {
                // Get the newly created user's ID
                $user_id = $insert_result['insert_id'];
                
                // Debug output
                error_log("Debug - New user created - ID: $user_id, Username: $username");
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                
                // Set secure cookie
                setcookie('user_session', $user_id, [
                    'expires' => time() + 3600,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                // Debug output
                error_log("Debug - Session after signup: " . print_r($_SESSION, true));
                
                header('Location: index.php');
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - Secure App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Sign Up</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="signup.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Password must be at least 6 characters long</small>
                </div>
                
                <button type="submit">Sign Up</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html> 