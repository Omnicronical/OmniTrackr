<?php
/**
 * Tag Controller
 * 
 * Handles tag CRUD operations
 */

require_once __DIR__ . '/../models/Tag.php';

class TagController {
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
     * Create a new tag
     * 
     * @param array $data Tag data
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function create($data, $user_id) {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Tag name is required',
                        'details' => []
                    ]
                ];
            }

            $tag = new Tag($this->db);

            // Check if tag name already exists for this user
            if ($tag->nameExistsForUser($user_id, $data['name'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_NAME',
                        'message' => 'Tag name already exists',
                        'details' => []
                    ]
                ];
            }

            // Create tag
            $tag->user_id = $user_id;
            $tag->name = $data['name'];
            $tag->color = $data['color'] ?? '#C0C0C0';

            $tag_id = $tag->create();

            if ($tag_id) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $tag_id,
                        'user_id' => $tag->user_id,
                        'name' => $tag->name,
                        'color' => $tag->color
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to create tag',
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
     * Get all tags for a user
     * 
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function getAll($user_id) {
        try {
            $tag = new Tag($this->db);
            $tags = $tag->getAllByUser($user_id);

            return [
                'success' => true,
                'data' => $tags
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
     * Get a single tag
     * 
     * @param int $id Tag ID
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function get($id, $user_id) {
        try {
            $tag = new Tag($this->db);

            if (!$tag->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Tag not found',
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
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $tag->id,
                    'user_id' => $tag->user_id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                    'created_at' => $tag->created_at,
                    'updated_at' => $tag->updated_at
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
     * Update a tag
     * 
     * @param int $id Tag ID
     * @param array $data Updated tag data
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function update($id, $data, $user_id) {
        try {
            $tag = new Tag($this->db);

            if (!$tag->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Tag not found',
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
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            // Validate name if provided
            if (isset($data['name'])) {
                if (empty($data['name'])) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'message' => 'Tag name cannot be empty',
                            'details' => []
                        ]
                    ];
                }

                // Check if new name already exists for this user (excluding current tag)
                if ($tag->nameExistsForUser($user_id, $data['name'], $id)) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'DUPLICATE_NAME',
                            'message' => 'Tag name already exists',
                            'details' => []
                        ]
                    ];
                }

                $tag->name = $data['name'];
            }

            // Update color if provided
            if (isset($data['color'])) {
                $tag->color = $data['color'];
            }

            if ($tag->update()) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $tag->id,
                        'user_id' => $tag->user_id,
                        'name' => $tag->name,
                        'color' => $tag->color
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to update tag',
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
     * Delete a tag
     * 
     * @param int $id Tag ID
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function delete($id, $user_id) {
        try {
            $tag = new Tag($this->db);

            if (!$tag->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Tag not found',
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
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            // Delete tag (cascade will handle activity_tags)
            if ($tag->delete()) {
                return [
                    'success' => true,
                    'data' => [
                        'message' => 'Tag deleted successfully'
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to delete tag',
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
