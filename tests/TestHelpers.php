<?php
/**
 * Test Helper Functions
 * 
 * Utility functions for generating test data
 */

class TestHelpers {
    /**
     * Generate a random string
     * 
     * @param int $length Length of string
     * @return string Random string
     */
    public static function randomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    /**
     * Generate a random email
     * 
     * @return string Random email address
     */
    public static function randomEmail() {
        return self::randomString(8) . '@' . self::randomString(6) . '.com';
    }

    /**
     * Generate a random username
     * 
     * @return string Random username
     */
    public static function randomUsername() {
        return 'user_' . self::randomString(8);
    }

    /**
     * Generate a random password
     * 
     * @param int $length Length of password
     * @return string Random password
     */
    public static function randomPassword($length = 12) {
        return self::randomString($length);
    }

    /**
     * Generate random user data
     * 
     * @return array User data
     */
    public static function randomUserData() {
        return [
            'username' => self::randomUsername(),
            'email' => self::randomEmail(),
            'password' => self::randomPassword()
        ];
    }

    /**
     * Clean up test data from database
     * 
     * @param mysqli $db Database connection
     * @param string $username Username to delete
     */
    public static function cleanupUser($db, $username) {
        $stmt = $db->prepare("DELETE FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up all test users
     * 
     * @param mysqli $db Database connection
     */
    public static function cleanupAllTestUsers($db) {
        $stmt = $db->prepare("DELETE FROM users WHERE username LIKE 'user_%'");
        if ($stmt) {
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up session by ID
     * 
     * @param mysqli $db Database connection
     * @param string $session_id Session ID to delete
     */
    public static function cleanupSession($db, $session_id) {
        $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("s", $session_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up all sessions for a user
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     */
    public static function cleanupUserSessions($db, $user_id) {
        $stmt = $db->prepare("DELETE FROM sessions WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Get user by username
     * 
     * @param mysqli $db Database connection
     * @param string $username Username
     * @return array|null User data or null if not found
     */
    public static function getUserByUsername($db, $username) {
        $stmt = $db->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        return null;
    }

    /**
     * Check if password is hashed (bcrypt)
     * 
     * @param string $hash Password hash
     * @return bool True if hash is bcrypt format
     */
    public static function isBcryptHash($hash) {
        return preg_match('/^\$2[ayb]\$.{56}$/', $hash) === 1;
    }

    /**
     * Generate a random category name
     * 
     * @return string Random category name
     */
    public static function randomCategoryName() {
        $categories = ['Work', 'Personal', 'Project', 'Study', 'Health', 'Finance', 'Travel', 'Hobby'];
        return $categories[array_rand($categories)] . '_' . self::randomString(6);
    }

    /**
     * Generate a random tag name
     * 
     * @return string Random tag name
     */
    public static function randomTagName() {
        $tags = ['Todo', 'InProgress', 'Done', 'Blocked', 'Review', 'Urgent', 'Low', 'High'];
        return $tags[array_rand($tags)] . '_' . self::randomString(6);
    }

    /**
     * Generate a random color hex code
     * 
     * @return string Random color in hex format
     */
    public static function randomColor() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate random category data
     * 
     * @return array Category data
     */
    public static function randomCategoryData() {
        return [
            'name' => self::randomCategoryName(),
            'color' => self::randomColor()
        ];
    }

    /**
     * Generate random tag data
     * 
     * @return array Tag data
     */
    public static function randomTagData() {
        return [
            'name' => self::randomTagName(),
            'color' => self::randomColor()
        ];
    }

    /**
     * Clean up category by ID
     * 
     * @param mysqli $db Database connection
     * @param int $category_id Category ID to delete
     */
    public static function cleanupCategory($db, $category_id) {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up all categories for a user
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     */
    public static function cleanupUserCategories($db, $user_id) {
        $stmt = $db->prepare("DELETE FROM categories WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up tag by ID
     * 
     * @param mysqli $db Database connection
     * @param int $tag_id Tag ID to delete
     */
    public static function cleanupTag($db, $tag_id) {
        $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $tag_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up all tags for a user
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     */
    public static function cleanupUserTags($db, $user_id) {
        $stmt = $db->prepare("DELETE FROM tags WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up activity by ID
     * 
     * @param mysqli $db Database connection
     * @param int $activity_id Activity ID to delete
     */
    public static function cleanupActivity($db, $activity_id) {
        $stmt = $db->prepare("DELETE FROM activities WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clean up all activities for a user
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     */
    public static function cleanupUserActivities($db, $user_id) {
        $stmt = $db->prepare("DELETE FROM activities WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Get category by ID
     * 
     * @param mysqli $db Database connection
     * @param int $category_id Category ID
     * @return array|null Category data or null if not found
     */
    public static function getCategoryById($db, $category_id) {
        $stmt = $db->prepare("SELECT id, user_id, name, color FROM categories WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();
            $stmt->close();
            return $category;
        }
        return null;
    }

    /**
     * Get all categories for a user
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     * @return array Array of categories
     */
    public static function getCategoriesByUser($db, $user_id) {
        $stmt = $db->prepare("SELECT id, user_id, name, color FROM categories WHERE user_id = ? ORDER BY name");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            $stmt->close();
            return $categories;
        }
        return [];
    }

    /**
     * Count activities for a category
     * 
     * @param mysqli $db Database connection
     * @param int $category_id Category ID
     * @return int Number of activities
     */
    public static function countActivitiesForCategory($db, $category_id) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM activities WHERE category_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['count'];
        }
        return 0;
    }

    /**
     * Generate a random activity title
     * 
     * @return string Random activity title
     */
    public static function randomActivityTitle() {
        $prefixes = ['Complete', 'Review', 'Update', 'Create', 'Fix', 'Implement', 'Test'];
        $subjects = ['Project', 'Task', 'Feature', 'Bug', 'Document', 'Report', 'Analysis'];
        return $prefixes[array_rand($prefixes)] . ' ' . $subjects[array_rand($subjects)] . ' ' . self::randomString(6);
    }

    /**
     * Generate a random activity description
     * 
     * @return string Random activity description
     */
    public static function randomActivityDescription() {
        $descriptions = [
            'This is an important task that needs attention',
            'Working on this project milestone',
            'Need to complete this by end of week',
            'High priority item for the team',
            'Follow up on previous discussion'
        ];
        return $descriptions[array_rand($descriptions)] . ' - ' . self::randomString(10);
    }

    /**
     * Generate random activity data
     * 
     * @param int|null $category_id Optional category ID
     * @param array $tag_ids Optional array of tag IDs
     * @return array Activity data
     */
    public static function randomActivityData($category_id = null, $tag_ids = []) {
        $data = [
            'title' => self::randomActivityTitle(),
            'description' => self::randomActivityDescription()
        ];

        if ($category_id !== null) {
            $data['category_id'] = $category_id;
        }

        if (!empty($tag_ids)) {
            $data['tag_ids'] = $tag_ids;
        }

        return $data;
    }

    /**
     * Get activity by ID
     * 
     * @param mysqli $db Database connection
     * @param int $activity_id Activity ID
     * @return array|null Activity data or null if not found
     */
    public static function getActivityById($db, $activity_id) {
        $stmt = $db->prepare("SELECT id, user_id, category_id, title, description, created_at, updated_at FROM activities WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $activity = $result->fetch_assoc();
            $stmt->close();
            return $activity;
        }
        return null;
    }

    /**
     * Get all activities for a user
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     * @return array Array of activities
     */
    public static function getActivitiesByUser($db, $user_id) {
        $stmt = $db->prepare("SELECT id, user_id, category_id, title, description, created_at, updated_at FROM activities WHERE user_id = ? ORDER BY created_at DESC");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            $stmt->close();
            return $activities;
        }
        return [];
    }

    /**
     * Get tags for an activity
     * 
     * @param mysqli $db Database connection
     * @param int $activity_id Activity ID
     * @return array Array of tag IDs
     */
    public static function getActivityTags($db, $activity_id) {
        $stmt = $db->prepare("SELECT tag_id FROM activity_tags WHERE activity_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $tags = [];
            while ($row = $result->fetch_assoc()) {
                $tags[] = $row['tag_id'];
            }
            $stmt->close();
            return $tags;
        }
        return [];
    }
}
