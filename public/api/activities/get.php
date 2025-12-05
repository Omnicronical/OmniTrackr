<?php
/**
 * Get Single Activity Endpoint
 * GET /api/activities/get.php?id={id}
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

    // Validate ID parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Activity ID is required',
                'details' => []
            ]
        ]);
        exit();
    }

    $id = intval($_GET['id']);

    // Get activity
    $database = new Database();
    $db = $database->getConnection();
    $controller = new ActivityController($db);
    $result = $controller->get($id, $user['user_id']);

    if ($result['success']) {
        http_response_code(200);
    } else {
        // Set appropriate status code based on error
        $status_code = 400;
        if (isset($result['error']['code'])) {
            switch ($result['error']['code']) {
                case 'FORBIDDEN':
                    $status_code = 403;
                    break;
                case 'NOT_FOUND':
                    $status_code = 404;
                    break;
                case 'SERVER_ERROR':
                    $status_code = 500;
                    break;
            }
        }
        http_response_code($status_code);
    }

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
