<?php
/**
 * List Categories API Endpoint
 * GET /api/categories/list.php
 */

// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../../src/config/database.php';
    require_once __DIR__ . '/../../../src/controllers/CategoryController.php';
    require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only GET requests are allowed',
                'details' => []
            ]
        ]);
        exit();
    }

    // Authenticate user
    $authMiddleware = new AuthMiddleware();
    $user = $authMiddleware->authenticate();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required',
                'details' => []
            ]
        ]);
        exit();
    }

    $user_id = $user['user_id'];

    // Get categories
    $database = new Database();
    $db = $database->getConnection();
    $controller = new CategoryController($db);
    $result = $controller->getAll($user_id);

    http_response_code(200);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'An error occurred',
            'details' => ['error' => $e->getMessage()]
        ]
    ]);
}
