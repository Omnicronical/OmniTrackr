<?php
/**
 * List Tags API Endpoint
 * GET /api/tags/list.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/controllers/TagController.php';
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
$auth = AuthMiddleware::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode($auth);
    exit();
}

$user_id = $auth['user_id'];

// Get all tags
$controller = new TagController();
$result = $controller->getAll($user_id);

http_response_code(200);
echo json_encode($result);
