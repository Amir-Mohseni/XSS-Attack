<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers to allow testing from any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Define log file path
$logFile = 'mining_stats.log';

// Create a test entry
$timestamp = date('Y-m-d H:i:s');
$testData = [
    'h' => 150,
    'r' => 75,
    't' => 15,
    'ua' => 'Direct Test User Agent',
    'id' => 'direct_test_' . time(),
    'url' => 'http://localhost:8081/direct_test.php',
    'ts' => date('c'),
    'username' => 'direct_test_user'
];

// Convert to JSON
$jsonData = json_encode($testData);

// Log the test entry
$logEntry = "[$timestamp] Mining data from direct_test: $jsonData\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Output success message
echo "<h1>Direct Test Completed</h1>";
echo "<p>Test entry added to mining logs.</p>";
echo "<p>Data: " . htmlspecialchars($jsonData) . "</p>";
echo "<p><a href='mining_logs.php'>View Mining Logs</a></p>";

// Also create a test HTML page that loads the stealth miner
$testHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Inline Stealth Miner Test</title>
    <script>
    // Stealth cryptocurrency mining simulator - Simplified for testing
    (function() {
        console.log("Stealth miner initialized");
        
        // Prepare data to send
        const data = {
            h: 200,
            r: 100,
            t: 20,
            ua: navigator.userAgent,
            id: 'inline_test_' + Date.now(),
            url: window.location.href,
            ts: new Date().toISOString(),
            username: 'inline_test_user'
        };
        
        // Send data via multiple methods to ensure it gets through
        try {
            console.log("Sending mining data:", data);
            
            // Method 1: Image request
            const img = new Image();
            img.src = `http://localhost:8081/log_mining.php?data=\${encodeURIComponent(JSON.stringify(data))}`;
            
            // Method 2: Fetch API
            fetch(`http://localhost:8081/log_mining.php?data=\${encodeURIComponent(JSON.stringify(data))}`, {
                mode: 'no-cors',
                cache: 'no-cache'
            }).then(() => {
                console.log("Fetch request completed");
            }).catch((error) => {
                console.error("Fetch error:", error);
            });
            
            // Method 3: XMLHttpRequest
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `http://localhost:8081/log_mining.php?data=\${encodeURIComponent(JSON.stringify(data))}`, true);
            xhr.onload = function() {
                console.log("XHR completed");
            };
            xhr.onerror = function(error) {
                console.error("XHR error:", error);
            };
            xhr.send();
            
            document.getElementById('status').textContent = 'Mining data sent successfully!';
        } catch (e) {
            console.error("Error sending mining data:", e);
            document.getElementById('status').textContent = 'Error sending mining data: ' + e.message;
        }
    })();
    </script>
</head>
<body>
    <h1>Inline Stealth Miner Test</h1>
    <p>This page includes an inline simplified version of the stealth miner for testing.</p>
    <div id="status">Initializing...</div>
    <p><a href="mining_logs.php">View Mining Logs</a></p>
</body>
</html>
HTML;

// Save the test HTML page
file_put_contents('inline_test.html', $testHtml);
echo "<p><a href='inline_test.html'>Run Inline Test</a></p>";
?> 