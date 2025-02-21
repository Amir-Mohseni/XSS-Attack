<?php
$logs = file_get_contents('stolen_cookies.log');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stolen Cookies Log</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        pre { background: #f4f4f4; padding: 15px; }
    </style>
</head>
<body>
    <h1>Stolen Cookies Log</h1>
    <pre><?php echo htmlspecialchars($logs); ?></pre>
</body>
</html> 