<?php
/**
 * Database Configuration
 * 
 * This file handles database connection and configuration loading
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        $this->loadEnv();
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'omnitrackr';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->port = getenv('DB_PORT') ?: '3306';
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * Get database connection
     * 
     * @return mysqli Database connection object
     * @throws Exception if connection fails
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name,
                $this->port
            );

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            // Set charset to utf8mb4
            $this->conn->set_charset("utf8mb4");

        } catch (Exception $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Test database connectivity
     * 
     * @return bool True if connection successful
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $this->closeConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
