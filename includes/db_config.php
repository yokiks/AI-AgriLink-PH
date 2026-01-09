<?php
/**
 * Database Configuration for AI-AgriLinkPH
 * Configure your database connection settings here
 * 
 * For Replit: Set environment variables in Replit Secrets:
 * - DB_HOST
 * - DB_USER
 * - DB_PASS
 * - DB_NAME
 */

// Database credentials - supports environment variables for Replit deployment
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'agrilink_db');

// Create database connection
function get_db_connection() {
    // For Replit: Try connecting directly to database (database may already exist)
    // If that fails, try creating it
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // If connection failed, try without database (to create it)
    if ($conn->connect_error) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            // Don't die in production - return false instead
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Connection failed: " . $conn->connect_error);
            }
            return false;
        }
        
        // Create database if it doesn't exist (only if we have permission)
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        if ($conn->query($sql) === FALSE) {
            error_log("Error creating database: " . $conn->error);
            // Try to continue with existing connection
        }
        
        // Select the database
        $conn->select_db(DB_NAME);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Test database connection
function test_db_connection() {
    $conn = get_db_connection();
    if ($conn) {
        $conn->close();
        return true;
    }
    return false;
}