<?php
/**
 * Category Controller
 * 
 * Handles category CRUD operations
 */

require_once __DIR__ . '/../models/Category.php';

class CategoryController {
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
     * Create a new category
     * 
     * @param array $data Category data
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
                        'message' => 'Category name is required',
                        'details' => []
                    ]
                ];
            }

            $category = new Category($this->db);

            // Check if category name already exists for this user
            if ($category->nameExistsForUser($user_id, $data['name'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_NAME',
                        'message' => 'Category name already exists',
                        'details' => []
                    ]
                ];
            }

            // Create category
            $category->user_id = $user_id;
            $category->name = $data['name'];
            $category->color = $data['color'] ?? '#FFD700';

            $category_id = $category->create();

            if ($category_id) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $category_id,
                        'user_id' => $category->user_id,
                        'name' => $category->name,
                        'color' => $category->color
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to create category',
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
     * Get all categories for a user
     * 
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function getAll($user_id) {
        try {
            $category = new Category($this->db);
            $categories = $category->getAllByUser($user_id);

            return [
                'success' => true,
                'data' => $categories
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
     * Get a single category
     * 
     * @param int $id Category ID
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function get($id, $user_id) {
        try {
            $category = new Category($this->db);

            if (!$category->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Category not found',
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
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'user_id' => $category->user_id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at
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
     * Update a category
     * 
     * @param int $id Category ID
     * @param array $data Updated category data
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function update($id, $data, $user_id) {
        try {
            $category = new Category($this->db);

            if (!$category->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Category not found',
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
                            'message' => 'Category name cannot be empty',
                            'details' => []
                        ]
                    ];
                }

                // Check if new name already exists for this user (excluding current category)
                if ($category->nameExistsForUser($user_id, $data['name'], $id)) {
                    return [
                        'success' => false,
                        'error' => [
                            'code' => 'DUPLICATE_NAME',
                            'message' => 'Category name already exists',
                            'details' => []
                        ]
                    ];
                }

                $category->name = $data['name'];
            }

            // Update color if provided
            if (isset($data['color'])) {
                $category->color = $data['color'];
            }

            if ($category->update()) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $category->id,
                        'user_id' => $category->user_id,
                        'name' => $category->name,
                        'color' => $category->color
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to update category',
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
     * Delete a category
     * 
     * @param int $id Category ID
     * @param int $user_id User ID
     * @return array Response with success status and data/error
     */
    public function delete($id, $user_id) {
        try {
            $category = new Category($this->db);

            if (!$category->findById($id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Category not found',
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
                        'message' => 'Access denied',
                        'details' => []
                    ]
                ];
            }

            // Delete category (cascade will handle activities)
            if ($category->delete()) {
                return [
                    'success' => true,
                    'data' => [
                        'message' => 'Category deleted successfully'
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to delete category',
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
