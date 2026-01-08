<?php
/**
 * Database Configuration for AI-AgriLinkPH
 * Configure your database connection settings here
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Change this to your database username
define('DB_PASS', '');               // Change this to your database password
define('DB_NAME', 'agrilink_db');    // Database name

// Create database connection
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if ($conn->query($sql) === FALSE) {
        die("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db(DB_NAME);
    
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