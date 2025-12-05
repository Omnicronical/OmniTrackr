<?php
/**
 * Create Tag API Endpoint
 * POST /api/tags/create.php
 */

// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only POST requests are allowed',
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

    // Create tag with database initialization
    $database = new Database();
    $db = $database->getConnection();
    $controller = new TagController($db);
    $result = $controller->create($data, $user_id);

    if ($result['success']) {
        http_response_code(201);
    } else {
        if ($result['error']['code'] === 'DUPLICATE_NAME') {
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
