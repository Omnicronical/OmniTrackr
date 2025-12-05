<?php
/**
 * List Activities Endpoint
 * GET /api/activities/list.php
 */

// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once __DIR__ . '/../../../src/config/database.php';
    require_once __DIR__ . '/../../../src/controllers/ActivityController.php';
    require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Only allow GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only GET method is allowed',
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

    // Parse filters from query parameters
    $filters = [];

    // Category filter
    if (isset($_GET['category_ids']) && !empty($_GET['category_ids'])) {
        $filters['category_ids'] = array_map('intval', explode(',', $_GET['category_ids']));
    }

    // Tag filter
    if (isset($_GET['tag_ids']) && !empty($_GET['tag_ids'])) {
        $filters['tag_ids'] = array_map('intval', explode(',', $_GET['tag_ids']));
    }

    // Get activities
    $database = new Database();
    $db = $database->getConnection();
    $controller = new ActivityController($db);
    $result = $controller->getAll($user['user_id'], $filters);

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
