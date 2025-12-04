<?php
/**
 * Stats Controller
 * 
 * Handles statistics and analytics operations
 */

require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Tag.php';

class StatsController {
    private $db;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }
    }

    /**
     * Get overview statistics
     * 
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function getOverview($user_id) {
        try {
            // Get total activity count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM activities WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_activities = $result->fetch_assoc()['total'];
            $stmt->close();

            // Get total category count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM categories WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_categories = $result->fetch_assoc()['total'];
            $stmt->close();

            // Get total tag count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tags WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_tags = $result->fetch_assoc()['total'];
            $stmt->close();

            return [
                'success' => true,
                'data' => [
                    'total_activities' => (int)$total_activities,
                    'total_categories' => (int)$total_categories,
                    'total_tags' => (int)$total_tags
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve overview statistics',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Get category breakdown statistics
     * 
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function getCategoryBreakdown($user_id) {
        try {
            $query = "SELECT c.id, c.name, c.color, COUNT(a.id) as activity_count
                      FROM categories c
                      LEFT JOIN activities a ON c.id = a.category_id
                      WHERE c.user_id = ?
                      GROUP BY c.id, c.name, c.color
                      ORDER BY activity_count DESC, c.name ASC";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $breakdown = [];
            while ($row = $result->fetch_assoc()) {
                $breakdown[] = [
                    'category_id' => (int)$row['id'],
                    'category_name' => $row['name'],
                    'category_color' => $row['color'],
                    'activity_count' => (int)$row['activity_count']
                ];
            }
            $stmt->close();

            // Also include activities without a category
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM activities WHERE user_id = ? AND category_id IS NULL");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $uncategorized_count = $result->fetch_assoc()['count'];
            $stmt->close();

            if ($uncategorized_count > 0) {
                $breakdown[] = [
                    'category_id' => null,
                    'category_name' => 'Uncategorized',
                    'category_color' => '#CCCCCC',
                    'activity_count' => (int)$uncategorized_count
                ];
            }

            return [
                'success' => true,
                'data' => $breakdown
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve category breakdown',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Get tag distribution statistics
     * 
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function getTagDistribution($user_id) {
        try {
            $query = "SELECT t.id, t.name, t.color, COUNT(at.activity_id) as activity_count
                      FROM tags t
                      LEFT JOIN activity_tags at ON t.id = at.tag_id
                      WHERE t.user_id = ?
                      GROUP BY t.id, t.name, t.color
                      ORDER BY activity_count DESC, t.name ASC";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $distribution = [];
            while ($row = $result->fetch_assoc()) {
                $distribution[] = [
                    'tag_id' => (int)$row['id'],
                    'tag_name' => $row['name'],
                    'tag_color' => $row['color'],
                    'activity_count' => (int)$row['activity_count']
                ];
            }
            $stmt->close();

            return [
                'success' => true,
                'data' => $distribution
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve tag distribution',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Get timeline data
     * 
     * @param int $user_id User ID
     * @param int $days Number of days to include (default 30)
     * @return array Response with success status and data/error
     */
    public function getTimeline($user_id, $days = 30) {
        try {
            $query = "SELECT DATE(created_at) as date, COUNT(*) as count
                      FROM activities
                      WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY date ASC";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }

            $stmt->bind_param("ii", $user_id, $days);
            $stmt->execute();
            $result = $stmt->get_result();

            $timeline = [];
            while ($row = $result->fetch_assoc()) {
                $timeline[] = [
                    'date' => $row['date'],
                    'count' => (int)$row['count']
                ];
            }
            $stmt->close();

            return [
                'success' => true,
                'data' => $timeline
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve timeline data',
                    'details' => []
                ]
            ];
        }
    }
}
