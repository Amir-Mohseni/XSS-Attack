<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get data from either GET or POST
$data = $_GET['data'] ?? $_POST['data'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');

// Allow CORS for testing in local environment
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug: Log the raw request
error_log("Received mining request from $ip with data length: " . strlen($data));

// Ensure the log file exists and is writable
$logFile = 'mining_stats.log';
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0666);
    error_log("Created log file: $logFile");
}

// Check if the log file is writable
if (!is_writable($logFile)) {
    error_log("Log file is NOT writable! Permissions: " . substr(sprintf('%o', fileperms($logFile)), -4));
    // Try to fix permissions
    chmod($logFile, 0666);
    if (!is_writable($logFile)) {
        error_log("Failed to fix permissions for $logFile");
    }
}

if (!empty($data)) {
    // Sanitize the data before logging
    $data = str_replace("\n", "", $data);
    $data = str_replace("\r", "", $data);
    
    // Decode the JSON data to extract and validate username
    $jsonData = json_decode($data, true);
    if (is_array($jsonData)) {
        // Ensure we have at least the basic required fields
        if (!isset($jsonData['h'])) $jsonData['h'] = 0;
        if (!isset($jsonData['r'])) $jsonData['r'] = 0;
        if (!isset($jsonData['t'])) $jsonData['t'] = 0;
        
        // Sanitize username to prevent log injection
        if (isset($jsonData['username'])) {
            $jsonData['username'] = preg_replace('/[^a-zA-Z0-9_\-\.:]/i', '', $jsonData['username']);
        } else {
            $jsonData['username'] = 'unknown_user';
        }
        
        // Add timestamp if not present
        if (!isset($jsonData['ts'])) {
            $jsonData['ts'] = date('c');
        }
        
        // Re-encode the data with sanitized values
        $data = json_encode($jsonData);
    }
    
    // Log the mining data
    $logEntry = "[$timestamp] Mining data from $ip: $data\n";
    $result = file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    if ($result === false) {
        // Log error if writing fails
        error_log("Failed to write to mining log file: $logFile");
        
        // Try to diagnose the issue
        $error = error_get_last();
        if ($error) {
            error_log("PHP Error: " . print_r($error, true));
        }
        
        // Check file permissions
        error_log("File permissions: " . substr(sprintf('%o', fileperms($logFile)), -4));
    } else {
        error_log("Successfully wrote mining data to log file. Bytes written: $result");
    }
}

// Return a small transparent GIF to avoid browser errors
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); 