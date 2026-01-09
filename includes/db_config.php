<?php

/**
 * Database Configuration for AI-AgriLinkPH
 * Uses PDO for database connections with environment variables
 * 
 * Configuration Options:
 * 1. Create a .env file in project root (recommended)
 * 2. Set environment variables in your hosting platform (Replit Secrets, etc.)
 * 3. Falls back to default localhost settings for local development
 */

// Load environment variables
require_once(__DIR__ . '/env_loader.php');

// Database credentials from environment variables
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'agrilink_db'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Debug mode
define('DEBUG_MODE', env('DEBUG_MODE', true));

// PDO connection instance (singleton pattern)
$pdo_instance = null;

/**
 * Get PDO database connection
 * 
 * @return PDO|false Returns PDO instance or false on failure
 */
function get_db_connection()
{
    global $pdo_instance;

    // Return existing connection if available
    if ($pdo_instance !== null) {
        return $pdo_instance;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    try {
        // Try connecting with database name first
        $dsn_with_db = $dsn . ";dbname=" . DB_NAME;
        $pdo_instance = new PDO($dsn_with_db, DB_USER, DB_PASS, $options);
        return $pdo_instance;
    } catch (PDOException $e) {
        // If database doesn't exist, try to create it
        if (
            strpos($e->getMessage(), 'Unknown database') !== false ||
            strpos($e->getMessage(), "doesn't exist") !== false
        ) {

            try {
                // Connect without database
                $pdo_temp = new PDO($dsn, DB_USER, DB_PASS, $options);

                // Create database
                $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` 
                                CHARACTER SET " . DB_CHARSET . " 
                                COLLATE " . DB_CHARSET . "_unicode_ci");

                // Now connect to the created database
                $dsn_with_db = $dsn . ";dbname=" . DB_NAME;
                $pdo_instance = new PDO($dsn_with_db, DB_USER, DB_PASS, $options);
                return $pdo_instance;
            } catch (PDOException $e2) {
                error_log("Database creation failed: " . $e2->getMessage());

                if (DEBUG_MODE) {
                    die("Database creation failed: " . $e2->getMessage());
                }
                return false;
            }
        }

        error_log("Database connection failed: " . $e->getMessage());

        if (DEBUG_MODE) {
            die("Connection failed: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Test database connection
 * 
 * @return bool True if connection successful
 */
function test_db_connection()
{
    try {
        $pdo = get_db_connection();
        if ($pdo) {
            // Test with a simple query
            $pdo->query("SELECT 1");
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Close database connection
 */
function close_db_connection()
{
    global $pdo_instance;
    $pdo_instance = null;
}

/**
 * Execute a prepared statement
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement|false
 */
function db_query($sql, $params = [])
{
    try {
        $pdo = get_db_connection();
        if (!$pdo) {
            return false;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());

        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

/**
 * Fetch single row from query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array|false
 */
function db_fetch_one($sql, $params = [])
{
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows from query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return array|false
 */
function db_fetch_all($sql, $params = [])
{
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

/**
 * Get last insert ID
 * 
 * @return string|false
 */
function db_last_insert_id()
{
    $pdo = get_db_connection();
    return $pdo ? $pdo->lastInsertId() : false;
}

/**
 * Get row count from last statement
 * 
 * @param PDOStatement $stmt
 * @return int
 */
function db_row_count($stmt)
{
    return $stmt ? $stmt->rowCount() : 0;
}
