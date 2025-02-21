<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid();
}
setcookie('user_session', $_SESSION['user_id'], time() + 3600, '/', '', false, false);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vulnerable Comment System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome to Our Comment System</h1>
        <p>This is a deliberately vulnerable application for educational purposes.</p>
        
        <div class="comment-form">
            <h2>Leave a Comment</h2>
            <form action="comments.php" method="POST">
                <input type="text" name="author" placeholder="Your Name" required>
                <textarea name="content" placeholder="Your Comment" required></textarea>
                <button type="submit">Post Comment</button>
            </form>
        </div>

        <div class="comments">
            <h2>Comments</h2>
            <?php
            require_once 'db.php';
            $result = $mysqli->query("SELECT * FROM comments ORDER BY created_at DESC");
            while ($row = $result->fetch_assoc()) {
                echo "<div class='comment'>";
                echo "<h3>" . $row['author'] . "</h3>";
                echo "<p>" . $row['content'] . "</p>";
                echo "<small>Posted on " . $row['created_at'] . "</small>";
                echo "</div>";
            }
            ?>
        </div>
    </div>
</body>
</html> 