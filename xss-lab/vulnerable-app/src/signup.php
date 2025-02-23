<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Check if username exists
    $check_query = "SELECT * FROM users WHERE username='$username'";
    $result = $mysqli->query($check_query);
    
    if ($result === false) {
        $error = "Database error: " . $mysqli->error;
    } else if ($result->num_rows > 0) {
        $error = "Username already exists";
    } else {
        // Vulnerable: Direct SQL injection possible
        $query = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
        
        if ($mysqli->query($query)) {
            // Get the newly created user's ID
            $user_id = (int)$mysqli->insert_id;
            
            // Debug output
            error_log("Debug - New user created - ID: $user_id, Username: $username");
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            // Set vulnerable cookie
            setcookie('user_session', $user_id, time() + 3600, '/', '', false, false);
            
            // Debug output
            error_log("Debug - Session after signup: " . print_r($_SESSION, true));
            
            // Verify user was created
            $verify_query = "SELECT * FROM users WHERE id = $user_id";
            $verify_result = $mysqli->query($verify_query);
            error_log("Debug - Verify user exists: " . ($verify_result->num_rows > 0 ? "Yes" : "No"));
            
            header('Location: index.php');
            exit();
        } else {
            $error = "Error creating account: " . $mysqli->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - Vulnerable App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Sign Up</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="signup.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
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