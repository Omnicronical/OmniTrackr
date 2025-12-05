<?php
/**
 * Get Tag Distribution Statistics
 * GET /api/stats/by-tag
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
    require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
    require_once __DIR__ . '/../../../src/controllers/StatsController.php';

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

    // Get tag distribution with database initialization
    $database = new Database();
    $db = $database->getConnection();
    $controller = new StatsController($db);
    $result = $controller->getTagDistribution($user_id);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(500);
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
