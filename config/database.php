<?php
/**
 * Database Configuration
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);  // Default MySQL port - change if needed
define('DB_NAME', 'atit_attendance');
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_CHARSET', 'utf8mb4');

/**
 * Database Connection Class using PDO
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // First connect without database to handle fresh setups
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $tempConnection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Create database if it doesn't exist
            $tempConnection->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tempConnection->exec("USE " . DB_NAME);
            
            // Now connect with the database selected
            $this->connection = $tempConnection;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration. Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function getDB() {
    return Database::getInstance()->getConnection();
}