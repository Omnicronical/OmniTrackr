<?php
/**
 * Activity Model
 * 
 * Handles activity data operations
 */

require_once __DIR__ . '/../config/database.php';

class Activity {
    private $conn;
    private $table_name = "activities";

    public $id;
    public $user_id;
    public $category_id;
    public $title;
    public $description;
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
     * Create a new activity
     * 
     * @return int|false Activity ID on success, false on failure
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, category_id, title, description) 
                  VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        // Use default empty description if not set
        $description = $this->description ?? '';

        $stmt->bind_param("iiss", $this->user_id, $this->category_id, $this->title, $description);

        if ($stmt->execute()) {
            $this->id = $this->conn->insert_id;
            $stmt->close();
            return $this->id;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find activity by ID
     * 
     * @param int $id Activity ID
     * @return bool True if activity found
     */
    public function findById($id) {
        $query = "SELECT id, user_id, category_id, title, description, created_at, updated_at 
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
            $this->category_id = $row['category_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get all activities for a user with optional filtering
     * 
     * @param int $user_id User ID
     * @param array $filters Optional filters (category_ids, tag_ids)
     * @return array Array of activities
     */
    public function getAllByUser($user_id, $filters = []) {
        $query = "SELECT DISTINCT a.id, a.user_id, a.category_id, a.title, a.description, 
                         a.created_at, a.updated_at 
                  FROM " . $this->table_name . " a";
        
        $conditions = ["a.user_id = ?"];
        $params = [$user_id];
        $types = "i";

        // Filter by category (OR logic - activity must have one of the specified categories)
        if (!empty($filters['category_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $conditions[] = "a.category_id IN ($placeholders)";
            foreach ($filters['category_ids'] as $cat_id) {
                $params[] = $cat_id;
                $types .= "i";
            }
        }

        // Filter by tags (AND logic - activity must have ALL specified tags)
        if (!empty($filters['tag_ids'])) {
            // Use subquery to ensure activity has ALL specified tags
            $tag_count = count($filters['tag_ids']);
            $placeholders = implode(',', array_fill(0, $tag_count, '?'));
            $conditions[] = "a.id IN (
                SELECT activity_id 
                FROM activity_tags 
                WHERE tag_id IN ($placeholders)
                GROUP BY activity_id 
                HAVING COUNT(DISTINCT tag_id) = ?
            )";
            foreach ($filters['tag_ids'] as $tag_id) {
                $params[] = $tag_id;
                $types .= "i";
            }
            $params[] = $tag_count;
            $types .= "i";
        }

        $query .= " WHERE " . implode(' AND ', $conditions);
        $query .= " ORDER BY a.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activities = [];
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }

        $stmt->close();
        return $activities;
    }

    /**
     * Update activity
     * 
     * @return bool True on success
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id = ?, title = ?, description = ? 
                  WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("issii", $this->category_id, $this->title, $this->description, 
                         $this->id, $this->user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Delete activity
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
     * Add tag to activity
     * 
     * @param int $tag_id Tag ID
     * @return bool True on success
     */
    public function addTag($tag_id) {
        $query = "INSERT INTO activity_tags (activity_id, tag_id) VALUES (?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $this->id, $tag_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Remove tag from activity
     * 
     * @param int $tag_id Tag ID
     * @return bool True on success
     */
    public function removeTag($tag_id) {
        $query = "DELETE FROM activity_tags WHERE activity_id = ? AND tag_id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $this->id, $tag_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Get all tags for this activity
     * 
     * @return array Array of tag IDs
     */
    public function getTags() {
        $query = "SELECT tag_id FROM activity_tags WHERE activity_id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tags = [];
        
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row['tag_id'];
        }

        $stmt->close();
        return $tags;
    }

    /**
     * Clear all tags from activity
     * 
     * @return bool True on success
     */
    public function clearTags() {
        $query = "DELETE FROM activity_tags WHERE activity_id = ?";
        
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
