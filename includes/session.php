<?php
/**
 * Session Management for AI-AgriLinkPH
 * Handles login, logout, session validation, and timeout
 */

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'agrilink_session');

// Set session name BEFORE starting session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

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
    // Simple authentication (for capstone level - can be enhanced with database)
    // Default credentials: admin / admin123
    $valid_users = [
        // 'admin' => password_hash('admin123', PASSWORD_DEFAULT),
        'user' => password_hash('user123', PASSWORD_DEFAULT)
    ];
    
    // For initial setup, check plain text (will be hashed after first login)
    if (isset($valid_users[$username])) {
        if (password_verify($password, $valid_users[$username]) || 
            // ($username === 'admin' && $password === 'admin123') ||
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

