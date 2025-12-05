<?php
/**
 * Activity Controller
 * 
 * Handles activity CRUD operations
 */

require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Tag.php';

class ActivityController {
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
     * Create a new activity
     * 
     * @param array $data Activity data
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function create($data, $user_id) {
        try {
            // Validate required fields
            if (empty($data['title'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Activity title is required',
                        'details' => []
                    ]
                ];
            }

            // Validate category if provided
            if (isset($data['category_id']) && !empty($data['category_id'])) {
                $category = new Category($this->db);
                if (!$category->findById($data['category_id'])) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'message' => 'Invalid category ID',
                            'details' => []
                        ]
                    ];
                }
                // Verify category belongs to user
                if ($category->user_id != $user_id) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'FORBIDDEN',
                            'message' => 'Category does not belong to user',
                            'details' => []
                        ]
                    ];
                }
            }

            // Validate tags if provided
            $tag_ids = [];
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $tag = new Tag($this->db);
                foreach ($data['tag_ids'] as $tag_id) {
                    if (!$tag->findById($tag_id)) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'VALIDATION_ERROR',
                                'message' => 'Invalid tag ID: ' . $tag_id,
                                'details' => []
                            ]
                        ];
                    }
                    // Verify tag belongs to user
                    if ($tag->user_id != $user_id) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'FORBIDDEN',
                                'message' => 'Tag does not belong to user',
                                'details' => []
                            ]
                        ];
                    }
                    $tag_ids[] = $tag_id;
                }
            }

            // Create activity
            $activity = new Activity($this->db);
            $activity->user_id = $user_id;
            $activity->category_id = $data['category_id'] ?? null;
            $activity->title = $data['title'];
            $activity->description = $data['description'] ?? '';

            $activity_id = $activity->create();

            if ($activity_id) {
                // Add tags
                foreach ($tag_ids as $tag_id) {
                    $activity->addTag($tag_id);
                }

                // Fetch the created activity with tags
                $activity->findById($activity_id);
                $tags = $activity->getTags();

                return [
                    'success' => true,
                    'data' => [
                        'id' => $activity->id,
                        'user_id' => $activity->user_id,
                        'category_id' => $activity->category_id,
                        'title' => $activity->title,
                        'description' => $activity->description,
                        'tag_ids' => $tags,
                        'created_at' => $activity->created_at,
                        'updated_at' => $activity->updated_at
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to create activity',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => ['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]
                ]
            ];
        }
    }

    /**
     * Get all activities for a user
     * 
     * @param int $user_id User ID
     * @param array $filters Optional filters
     * @return array Response with success status and data/error
     */
    public function getAll($user_id, $filters = []) {
        try {
            $activity = new Activity($this->db);
            $activities = $activity->getAllByUser($user_id, $filters);

            // Enrich activities with tag information and category names
            foreach ($activities as &$act) {
                $activityObj = new Activity($this->db);
                $activityObj->id = $act['id'];
                $act['tag_ids'] = $activityObj->getTags();
                
                // Get category name if category exists
                if ($act['category_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->bind_param("i", $act['category_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $act['category_name'] = $row['name'];
                    }
                    $stmt->close();
                } else {
                    $act['category_name'] = null;
                }
                
                // Get tag details with names
                $tag_details = [];
                foreach ($act['tag_ids'] as $tag_id) {
                    $stmt = $this->db->prepare("SELECT id, name FROM tags WHERE id = ?");
                    $stmt->bind_param("i", $tag_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $tag_details[] = [
                            'id' => $row['id'],
                            'name' => $row['name']
                        ];
                    }
                    $stmt->close();
                }
                $act['tags'] = $tag_details;
            }

            return [
                'success' => true,
                'data' => $activities
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Get a single activity
     * 
     * @param int $id Activity ID
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function get($id, $user_id) {
        try {
            $activity = new Activity($this->db);

            if (!$activity->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Activity not found',
                        'details' => []
                    ]
                ];
            }

            // Verify activity belongs to user
            if ($activity->user_id != $user_id) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            $tags = $activity->getTags();
            
            // Get category name if category exists
            $category_name = null;
            if ($activity->category_id) {
                $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
                $stmt->bind_param("i", $activity->category_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $category_name = $row['name'];
                }
                $stmt->close();
            }
            
            // Get tag details with names
            $tag_details = [];
            foreach ($tags as $tag_id) {
                $stmt = $this->db->prepare("SELECT id, name FROM tags WHERE id = ?");
                $stmt->bind_param("i", $tag_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $tag_details[] = [
                        'id' => $row['id'],
                        'name' => $row['name']
                    ];
                }
                $stmt->close();
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $activity->id,
                    'user_id' => $activity->user_id,
                    'category_id' => $activity->category_id,
                    'category_name' => $category_name,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'tag_ids' => $tags,
                    'tags' => $tag_details,
                    'created_at' => $activity->created_at,
                    'updated_at' => $activity->updated_at
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Update an activity
     * 
     * @param int $id Activity ID
     * @param array $data Updated activity data
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function update($id, $data, $user_id) {
        try {
            $activity = new Activity($this->db);

            if (!$activity->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Activity not found',
                        'details' => []
                    ]
                ];
            }

            // Verify activity belongs to user
            if ($activity->user_id != $user_id) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            // Validate title if provided
            if (isset($data['title'])) {
                if (empty($data['title'])) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'message' => 'Activity title cannot be empty',
                            'details' => []
                        ]
                    ];
                }
                $activity->title = $data['title'];
            }

            // Update description if provided
            if (isset($data['description'])) {
                $activity->description = $data['description'];
            }

            // Validate and update category if provided
            if (isset($data['category_id'])) {
                if (!empty($data['category_id'])) {
                    $category = new Category($this->db);
                    if (!$category->findById($data['category_id'])) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'VALIDATION_ERROR',
                                'message' => 'Invalid category ID',
                                'details' => []
                            ]
                        ];
                    }
                    // Verify category belongs to user
                    if ($category->user_id != $user_id) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'FORBIDDEN',
                                'message' => 'Category does not belong to user',
                                'details' => []
                            ]
                        ];
                    }
                }
                $activity->category_id = $data['category_id'] ?: null;
            }

            // Update tags if provided
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                // Validate all tags first
                $tag = new Tag($this->db);
                foreach ($data['tag_ids'] as $tag_id) {
                    if (!$tag->findById($tag_id)) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'VALIDATION_ERROR',
                                'message' => 'Invalid tag ID: ' . $tag_id,
                                'details' => []
                            ]
                        ];
                    }
                    // Verify tag belongs to user
                    if ($tag->user_id != $user_id) {
                        return [
                            'success' => false,
                            'error' => [
                                'code' => 'FORBIDDEN',
                                'message' => 'Tag does not belong to user',
                                'details' => []
                            ]
                        ];
                    }
                }

                // Clear existing tags and add new ones
                $activity->clearTags();
                foreach ($data['tag_ids'] as $tag_id) {
                    $activity->addTag($tag_id);
                }
            }

            if ($activity->update()) {
                $tags = $activity->getTags();

                return [
                    'success' => true,
                    'data' => [
                        'id' => $activity->id,
                        'user_id' => $activity->user_id,
                        'category_id' => $activity->category_id,
                        'title' => $activity->title,
                        'description' => $activity->description,
                        'tag_ids' => $tags
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to update activity',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Delete an activity
     * 
     * @param int $id Activity ID
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function delete($id, $user_id) {
        try {
            $activity = new Activity($this->db);

            if (!$activity->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Activity not found',
                        'details' => []
                    ]
                ];
            }

            // Verify activity belongs to user
            if ($activity->user_id != $user_id) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            // Delete activity (cascade will handle activity_tags)
            if ($activity->delete()) {
                return [
                    'success' => true,
                    'data' => [
                        'message' => 'Activity deleted successfully'
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to delete activity',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }
}
