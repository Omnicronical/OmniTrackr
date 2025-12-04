<?php
/**
 * Delete Category API Endpoint
 * DELETE /api/categories/delete.php?id={id}
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/controllers/CategoryController.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Only DELETE requests are allowed',
            'details' => []
        ]
    ]);
    exit();
}

// Authenticate user
$auth = AuthMiddleware::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode($auth);
    exit();
}

$user_id = $auth['user_id'];

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

// Delete category
$controller = new CategoryController();
$result = $controller->delete($category_id, $user_id);

if ($result['success']) {
    http_response_code(200);
} else {
    if ($result['error']['code'] === 'NOT_FOUND') {
        http_response_code(404);
    } elseif ($result['error']['code'] === 'FORBIDDEN') {
        http_response_code(403);
    } else {
        http_response_code(500);
    }
}

echo json_encode($result);
