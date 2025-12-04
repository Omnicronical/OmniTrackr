<?php
/**
 * Session Model
 * 
 * Handles session management and persistence
 */

require_once __DIR__ . '/../config/database.php';

class Session {
    private $conn;
    private $table_name = "sessions";

    public $id;
    public $user_id;
    public $expires_at;
    public $created_at;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    /**
     * Create a new session
     * 
     * @return string|false Session ID on success, false on failure
     */
    public function create() {
        // Generate a secure session ID
        $this->id = bin2hex(random_bytes(32));
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (id, user_id, expires_at) 
                  VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sis", $this->id, $this->user_id, $this->expires_at);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->id;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find session by ID
     * 
     * @param string $session_id
     * @return bool True if session found and valid
     */
    public function findById($session_id) {
        $query = "SELECT id, user_id, expires_at, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->expires_at = $row['expires_at'];
            $this->created_at = $row['created_at'];
            $stmt->close();
            
            // Check if session is expired
            if (strtotime($this->expires_at) < time()) {
                $this->delete();
                return false;
            }
            
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Delete session
     * 
     * @return bool True on success
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $this->id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Delete all sessions for a user
     * 
     * @param int $user_id
     * @return bool True on success
     */
    public function deleteByUserId($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Clean up expired sessions
     * 
     * @return bool True on success
     */
    public function cleanupExpired() {
        $query = "DELETE FROM " . $this->table_name . " WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Check if session exists and is valid
     * 
     * @param string $session_id
     * @return bool True if session is valid
     */
    public function isValid($session_id) {
        return $this->findById($session_id);
    }
}
