# Secure Comment System

A secure version of the previously vulnerable XSS Cookie Stealing Lab. This version demonstrates proper security practices to prevent common web vulnerabilities.

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

4. Access the application:
   - Secure App: http://localhost:8080

## Security Improvements

This branch contains a secure version of the application that addresses all the security vulnerabilities present in the original version. Here's what has been fixed:

### 1. SQL Injection Prevention

**Original Vulnerability:**
```php
$query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
```

**Secure Implementation:**
```php
$stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
```

All database queries now use prepared statements with parameterized queries to prevent SQL injection attacks.

### 2. XSS Prevention

**Original Vulnerability:**
```php
echo "<p>" . $row['content'] . "</p>";
```

**Secure Implementation:**
```php
echo "<p>" . htmlspecialchars($row['content']) . "</p>";
```

All user-generated content is now properly escaped using `htmlspecialchars()` to prevent Cross-Site Scripting (XSS) attacks.

### 3. Secure Cookie Handling

**Original Vulnerability:**
```php
setcookie('user_session', $_SESSION['user_id'], time() + 3600, '/', '', false, false);
```

**Secure Implementation:**
```php
setcookie('user_session', $_SESSION['user_id'], [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

Cookies now use secure settings:
- HttpOnly flag to prevent JavaScript access
- Secure flag to ensure HTTPS-only transmission
- SameSite attribute to prevent CSRF attacks

### 4. Password Hashing

**Original Vulnerability:**
```php
$query = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
```

**Secure Implementation:**
```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hashed_password);
```

Passwords are now properly hashed using PHP's `password_hash()` function with bcrypt.

### 5. Input Validation

**Original Vulnerability:**
No input validation was performed.

**Secure Implementation:**
```php
if (empty($username) || empty($password)) {
    $error = "Username and password are required";
} else if (strlen($password) < 6) {
    $error = "Password must be at least 6 characters long";
}
```

All user inputs are now validated before processing.

### 6. Error Handling

**Original Vulnerability:**
Error messages revealed sensitive information.

**Secure Implementation:**
```php
if ($result === false) {
    $error = "Database error. Please try again later.";
}
```

Generic error messages are now displayed to users, with detailed errors logged server-side.

## Additional Improvements

1. **Database Persistence**: Tables are only created if they don't exist, ensuring user data persists across sessions.

2. **Error Handling**: Added better error handling and debugging output to help troubleshoot issues.

3. **Session Management**: Improved session handling to ensure users stay logged in properly.

4. **UI Improvements**: Enhanced the user interface for better usability.

## Troubleshooting

### Common Issues

1. **Database Connection Fails**
   - Ensure all containers are running: `docker ps`
   - Check logs: `docker-compose logs db`
   - Try restarting with clean volumes

2. **Login/Signup Not Working**
   - Check database logs
   - Verify user table creation
   - Check for SQL errors
   - Make sure you're using the same username/password for login that you used for signup

3. **Comments Not Saving**
   - Verify you're properly logged in
   - Check for SQL errors in the logs

### Resetting the Application

If you encounter persistent issues, you can reset the application:

```bash
# Stop containers and remove volumes
docker-compose down -v

# Rebuild and start
docker-compose up --build
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

### Development Tips

1. **View Logs**
   ```bash
   # All containers
   docker-compose logs -f

   # Specific container
   docker-compose logs -f vulnerable-app
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

## Security Best Practices

1. **SQL Injection Prevention**:
   - Always use prepared statements
   - Never concatenate user input directly into SQL queries
   - Use parameterized queries for all database operations

2. **XSS Prevention**:
   - Always escape output with `htmlspecialchars()`
   - Consider Content Security Policy (CSP) headers
   - Validate and sanitize all user inputs

3. **Secure Cookie Handling**:
   - Use HttpOnly flag to prevent JavaScript access
   - Use Secure flag to ensure HTTPS-only transmission
   - Use SameSite attribute to prevent CSRF attacks
   - Consider using session tokens instead of user IDs

4. **Password Security**:
   - Always hash passwords with modern algorithms (bcrypt, Argon2)
   - Never store passwords in plaintext
   - Implement password complexity requirements
   - Consider implementing account lockout after failed attempts

5. **Input Validation**:
   - Validate all user inputs on both client and server side
   - Implement proper error handling
   - Use whitelisting approach for input validation

## License

This project is released under the MIT License. Use it responsibly. 