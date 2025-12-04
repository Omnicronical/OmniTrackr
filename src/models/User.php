<?php
/**
 * User Model
 * 
 * Handles user data operations and password management
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->conn = $database->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    /**
     * Create a new user
     * 
     * @return int|false User ID on success, false on failure
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, email, password_hash) 
                  VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        // Hash the password using bcrypt
        $hashed_password = password_hash($this->password_hash, PASSWORD_BCRYPT);

        $stmt->bind_param("sss", $this->username, $this->email, $hashed_password);

        if ($stmt->execute()) {
            $this->id = $this->conn->insert_id;
            $stmt->close();
            return $this->id;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find user by username
     * 
     * @param string $username
     * @return bool True if user found
     */
    public function findByUsername($username) {
        $query = "SELECT id, username, email, password_hash, created_at, updated_at 
                  FROM " . $this->table_name . " 
                  WHERE username = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find user by email
     * 
     * @param string $email
     * @return bool True if user found
     */
    public function findByEmail($email) {
        $query = "SELECT id, username, email, password_hash, created_at, updated_at 
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find user by ID
     * 
     * @param int $id
     * @return bool True if user found
     */
    public function findById($id) {
        $query = "SELECT id, username, email, password_hash, created_at, updated_at 
                  FROM " . $this->table_name . " 
                  WHERE id = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Verify password
     * 
     * @param string $password Plain text password
     * @return bool True if password matches
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Check if username exists
     * 
     * @param string $username
     * @return bool True if username exists
     */
    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Check if email exists
     * 
     * @param string $email
     * @return bool True if email exists
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Delete user
     * 
     * @return bool True on success
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $this->id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
