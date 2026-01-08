<?php
/**
 * Session Management for AI-AgriLinkPH
 * Handles login, logout, session validation, and timeout
 */

// Include database configuration
require_once(__DIR__ . '/db_config.php');
// Include email configuration
require_once(__DIR__ . '/../email_config.php');

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'agrilink_session');

// Set session name BEFORE starting session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Initialize users table if it doesn't exist
function init_users_table() {
    try {
        $conn = get_db_connection();
        
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql) === FALSE) {
            error_log("Error creating users table: " . $conn->error);
            return false;
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("Error initializing users table: " . $e->getMessage());
        return false;
    }
}

// Initialize password reset tokens table
function init_password_reset_tokens_table() {
    try {
        $conn = get_db_connection();
        
        $sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(100) NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_email (email),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql) === FALSE) {
            error_log("Error creating password_reset_tokens table: " . $conn->error);
            $conn->close();
            return false;
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("Error initializing password_reset_tokens table: " . $e->getMessage());
        return false;
    }
}

// Initialize tables on first load
init_users_table();
init_password_reset_tokens_table();

// Session timeout check
function check_session_timeout() {
    if (isset($_SESSION['last_activity'])) {
        $timeout_duration = SESSION_TIMEOUT;
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        if ($elapsed_time > $timeout_duration) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

// Check if user is logged in
function is_logged_in() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (!check_session_timeout()) {
        return false;
    }
    
    return true;
}

// Require login - redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        // Store the current URL to redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: login.php');
        exit;
    }
}

// Login function
function login($username, $password) {
    // First, try to authenticate from database
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                    
                    $stmt->close();
                    $conn->close();
                    return true;
                }
            }
            
            $stmt->close();
        }
        $conn->close();
    } catch (Exception $e) {
        error_log("Database login error: " . $e->getMessage());
        // Fall through to hardcoded users
    }
    
    // Fallback to hardcoded users for backward compatibility
    $valid_users = [
        'user' => password_hash('user123', PASSWORD_DEFAULT)
    ];
    
    if (isset($valid_users[$username])) {
        if (password_verify($password, $valid_users[$username]) || 
            ($username === 'user' && $password === 'user123')) {
            
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = date('Y-m-d H:i:s');
            
            return true;
        }
    }
    
    return false;
}

// Logout function
function logout() {
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Get logged in user
function get_logged_in_user() {
    return $_SESSION['username'] ?? null;
}

// Get session info
function get_session_info() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'username' => $_SESSION['username'] ?? 'Unknown',
        'login_time' => $_SESSION['login_time'] ?? 'Unknown',
        'last_activity' => isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'Unknown',
        'time_remaining' => isset($_SESSION['last_activity']) ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) : 0
    ];
}

// Register user function
function register_user($username, $email, $password) {
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        return 'All fields are required.';
    }
    
    if (strlen($username) < 3) {
        return 'Username must be at least 3 characters long.';
    }
    
    if (strlen($username) > 50) {
        return 'Username must be less than 50 characters.';
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    
    if (strlen($password) < 6) {
        return 'Password must be at least 6 characters long.';
    }
    
    // Check database for existing username or email
    try {
        $conn = get_db_connection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                $conn->close();
                return 'Username already exists. Please choose a different username.';
            }
            $stmt->close();
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                $conn->close();
                return 'Email address is already registered. Please use a different email.';
            }
            $stmt->close();
        }
        
        // Hash password and insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return true; // Success
            } else {
                $error = 'Registration failed. Please try again.';
                $stmt->close();
                $conn->close();
                return $error;
            }
        } else {
            $conn->close();
            return 'Database error. Please try again later.';
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return 'Registration failed. Please try again later.';
    }
}

// Request password reset - generates token and sends email
function request_password_reset($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    
    try {
        $conn = get_db_connection();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $conn->close();
            return true; // Return true for security (don't reveal if email exists)
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return true; // Return true for security (don't reveal if email exists)
        }
        
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $username = $user['username'];
        $stmt->close();
        
        // Generate secure token
        $token = bin2hex(random_bytes(32)); // 64 character token
        
        // Set expiration to 1 hour from now
        $expires_at = date('Y-m-d H:i:s', time() + 3600);
        
        // Invalidate any existing tokens for this user
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Insert new token
        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $conn->close();
            return true; // Return true for security
        }
        
        $stmt->bind_param("isss", $user_id, $email, $token, $expires_at);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            
            // Send email with reset link
            $reset_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                        "://" . $_SERVER['HTTP_HOST'] . 
                        dirname($_SERVER['PHP_SELF']) . 
                        "/reset_password.php?token=" . $token;
            
            $email_sent = send_password_reset_email($email, $username, $reset_url);
            
            // Return true even if email fails (for security)
            return true;
        } else {
            $stmt->close();
            $conn->close();
            return true; // Return true for security
        }
        
    } catch (Exception $e) {
        error_log("Password reset request error: " . $e->getMessage());
        return true; // Return true for security
    }
}

// Verify reset token
function verify_reset_token($token) {
    if (empty($token)) {
        return false;
    }
    
    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, user_id, email, expires_at, used FROM password_reset_tokens WHERE token = ? LIMIT 1");
        
        if (!$stmt) {
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return false;
        }
        
        $token_data = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        
        // Check if token is used
        if ($token_data['used'] == 1) {
            return false;
        }
        
        // Check if token is expired
        $expires_at = strtotime($token_data['expires_at']);
        if (time() > $expires_at) {
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Token verification error: " . $e->getMessage());
        return false;
    }
}

// Reset password using token
function reset_password($token, $new_password) {
    if (empty($token) || empty($new_password)) {
        return 'Token and password are required.';
    }
    
    if (strlen($new_password) < 6) {
        return 'Password must be at least 6 characters long.';
    }
    
    try {
        $conn = get_db_connection();
        
        // Verify token
        $stmt = $conn->prepare("SELECT id, user_id, email, expires_at, used FROM password_reset_tokens WHERE token = ? LIMIT 1");
        if (!$stmt) {
            $conn->close();
            return 'Invalid reset token.';
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return 'Invalid reset token.';
        }
        
        $token_data = $result->fetch_assoc();
        $stmt->close();
        
        // Check if token is used
        if ($token_data['used'] == 1) {
            $conn->close();
            return 'This reset link has already been used. Please request a new one.';
        }
        
        // Check if token is expired
        $expires_at = strtotime($token_data['expires_at']);
        if (time() > $expires_at) {
            $conn->close();
            return 'This reset link has expired. Please request a new one.';
        }
        
        $user_id = $token_data['user_id'];
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if (!$stmt) {
            $conn->close();
            return 'Database error. Please try again.';
        }
        
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt->close();
            $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
            return true; // Success
        } else {
            $stmt->close();
            $conn->close();
            return 'Failed to update password. Please try again.';
        }
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return 'An error occurred. Please try again later.';
    }
}

// Send password reset email
function send_password_reset_email($email, $username, $reset_url) {
    $subject = "Password Reset Request - AI-AgriLink PH";
    
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #15803d 0%, #16a34a 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
            .button { display: inline-block; background: #15803d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            .footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; border-radius: 0 0 10px 10px; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌾 AI-AgriLink PH</h1>
                <p>Password Reset Request</p>
            </div>
            <div class='content'>
                <p>Hello <strong>{$username}</strong>,</p>
                <p>We received a request to reset your password for your AI-AgriLink PH account.</p>
                <p>Click the button below to reset your password:</p>
                <p style='text-align: center;'>
                    <a href='{$reset_url}' class='button'>Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #15803d;'>{$reset_url}</p>
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This link will expire in 1 hour</li>
                        <li>If you didn't request this, please ignore this email</li>
                        <li>Your password will not change until you click the link above</li>
                    </ul>
                </div>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " AI-AgriLink PH · Sustainable Agriculture Dashboard</p>
                <p>This is an automated email. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return send_email($email, $subject, $html_message);
}
