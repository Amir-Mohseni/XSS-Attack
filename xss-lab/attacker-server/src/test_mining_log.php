<?php
// This is a simple test script to verify that the mining logs are working correctly

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the log file path
$logFile = 'mining_stats.log';

// Create the log file if it doesn't exist
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0666);
    echo "Created log file: $logFile<br>";
} else {
    echo "Log file already exists: $logFile<br>";
}

// Check if the log file is writable
if (is_writable($logFile)) {
    echo "Log file is writable<br>";
} else {
    echo "Log file is NOT writable! Permissions: " . substr(sprintf('%o', fileperms($logFile)), -4) . "<br>";
    echo "Attempting to fix permissions...<br>";
    chmod($logFile, 0666);
    if (is_writable($logFile)) {
        echo "Fixed permissions, log file is now writable<br>";
    } else {
        echo "Failed to fix permissions!<br>";
    }
}

// Create a test log entry
$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'];
$testData = [
    'h' => 100,
    'r' => 50,
    't' => 10,
    'ua' => 'Test User Agent',
    'id' => 'test_id_' . time(),
    'url' => 'http://localhost:8080/test',
    'ts' => date('c'),
    'username' => 'test_user'
];

$logEntry = "[$timestamp] Mining data from $ip: " . json_encode($testData) . "\n";
$result = file_put_contents($logFile, $logEntry, FILE_APPEND);

if ($result === false) {
    echo "Failed to write to log file!<br>";
    $error = error_get_last();
    if ($error) {
        echo "PHP Error: " . print_r($error, true) . "<br>";
    }
} else {
    echo "Successfully wrote test entry to log file<br>";
}

// Display the current log file contents
echo "<h2>Current Log File Contents:</h2>";
if (file_exists($logFile) && filesize($logFile) > 0) {
    $logs = file_get_contents($logFile);
    echo "<pre>" . htmlspecialchars($logs) . "</pre>";
} else {
    echo "Log file is empty or cannot be read<br>";
}

// Add a link to the mining logs page
echo "<p><a href='mining_logs.php'>View Mining Logs Dashboard</a></p>";

// Add a test button to simulate a mining report via JavaScript
echo <<<HTML
<h2>Test Mining Report via JavaScript</h2>
<button onclick="sendTestReport()">Send Test Mining Report</button>
<div id="result"></div>

<script>
function sendTestReport() {
    const data = {
        h: 200,
        r: 75,
        t: 20,
        ua: navigator.userAgent,
        id: 'js_test_' + Date.now(),
        url: window.location.href,
        ts: new Date().toISOString(),
        username: 'js_test_user'
    };
    
    // Method 1: Image
    const img = new Image();
    img.src = `log_mining.php?data=\${encodeURIComponent(JSON.stringify(data))}`;
    
    // Method 2: Fetch API
    fetch(`log_mining.php?data=\${encodeURIComponent(JSON.stringify(data))}`, {
        mode: 'no-cors',
        cache: 'no-cache'
    }).then(() => {
        document.getElementById('result').innerHTML = 'Test report sent successfully!';
    }).catch((error) => {
        document.getElementById('result').innerHTML = 'Error sending test report: ' + error;
    });
}
</script>
HTML; 