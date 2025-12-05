<?php
/**
 * Update Category API Endpoint
 * PUT /api/categories/update.php?id={id}
 */

// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
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

    // Only allow PUT requests
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only PUT requests are allowed',
                'details' => []
            ]
        ]);
        exit();
    }

    // Authenticate user
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();

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

    // Get category ID from query string
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Category ID is required',
                'details' => []
            ]
        ]);
        exit();
    }

    $category_id = intval($_GET['id']);

    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INVALID_JSON',
                'message' => 'Invalid JSON in request body',
                'details' => []
            ]
        ]);
        exit();
    }

    // Update category with database initialization
    $database = new Database();
    $db = $database->getConnection();
    $controller = new CategoryController($db);
    $result = $controller->update($category_id, $data, $user_id);

    if ($result['success']) {
        http_response_code(200);
    } else {
        if ($result['error']['code'] === 'NOT_FOUND') {
            http_response_code(404);
        } elseif ($result['error']['code'] === 'FORBIDDEN') {
            http_response_code(403);
        } elseif ($result['error']['code'] === 'DUPLICATE_NAME') {
            http_response_code(409);
        } elseif ($result['error']['code'] === 'VALIDATION_ERROR') {
            http_response_code(400);
        } else {
            http_response_code(500);
        }
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'An error occurred',
            'details' => []
        ]
    ]);
}
