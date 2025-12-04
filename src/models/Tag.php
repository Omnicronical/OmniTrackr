<?php
/**
 * Tag Model
 * 
 * Handles tag data operations
 */

require_once __DIR__ . '/../config/database.php';

class Tag {
    private $conn;
    private $table_name = "tags";

    public $id;
    public $user_id;
    public $name;
    public $color;
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
     * Create a new tag
     * 
     * @return int|false Tag ID on success, false on failure
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, name, color) 
                  VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        // Use default color if not set
        $color = $this->color ?: '#C0C0C0';

        $stmt->bind_param("iss", $this->user_id, $this->name, $color);

        if ($stmt->execute()) {
            $this->id = $this->conn->insert_id;
            $stmt->close();
            return $this->id;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find tag by ID
     * 
     * @param int $id Tag ID
     * @return bool True if tag found
     */
    public function findById($id) {
        $query = "SELECT id, user_id, name, color, created_at, updated_at 
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
            $this->user_id = $row['user_id'];
            $this->name = $row['name'];
            $this->color = $row['color'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get all tags for a user
     * 
     * @param int $user_id User ID
     * @return array Array of tags
     */
    public function getAllByUser($user_id) {
        $query = "SELECT id, user_id, name, color, created_at, updated_at 
                  FROM " . $this->table_name . " 
                  WHERE user_id = ? 
                  ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tags = [];
        
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }

        $stmt->close();
        return $tags;
    }

    /**
     * Update tag
     * 
     * @return bool True on success
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = ?, color = ? 
                  WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ssii", $this->name, $this->color, $this->id, $this->user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Delete tag
     * 
     * @return bool True on success
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $this->id, $this->user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Check if tag name exists for user
     * 
     * @param int $user_id User ID
     * @param string $name Tag name
     * @param int|null $exclude_id Tag ID to exclude from check (for updates)
     * @return bool True if name exists
     */
    public function nameExistsForUser($user_id, $name, $exclude_id = null) {
        if ($exclude_id !== null) {
            $query = "SELECT id FROM " . $this->table_name . " 
                      WHERE user_id = ? AND name = ? AND id != ? 
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("isi", $user_id, $name, $exclude_id);
        } else {
            $query = "SELECT id FROM " . $this->table_name . " 
                      WHERE user_id = ? AND name = ? 
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("is", $user_id, $name);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Count activities associated with this tag
     * 
     * @return int Number of activities
     */
    public function countActivities() {
        $query = "SELECT COUNT(*) as count FROM activity_tags WHERE tag_id = ?";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'];
    }
}
