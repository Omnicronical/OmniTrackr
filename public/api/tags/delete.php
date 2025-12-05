<?php
/**
 * Delete Tag API Endpoint
 * DELETE /api/tags/delete.php?id={id}
 */

// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../../src/config/database.php';
    require_once __DIR__ . '/../../../src/controllers/TagController.php';
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

    // Get tag ID from query string
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Tag ID is required',
                'details' => []
            ]
        ]);
        exit();
    }

    $tag_id = intval($_GET['id']);

    // Delete tag with database initialization
    $database = new Database();
    $db = $database->getConnection();
    $controller = new TagController($db);
    $result = $controller->delete($tag_id, $user_id);

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
