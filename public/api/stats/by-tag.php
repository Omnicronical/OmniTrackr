<?php
/**
 * Get Tag Distribution Statistics
 * 
 * GET /api/stats/by-tag
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/controllers/StatsController.php';

// Check authentication
$auth = AuthMiddleware::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode($auth);
    exit;
}

$user_id = $auth['user_id'];

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
    exit;
}

// Get tag distribution
$controller = new StatsController();
$result = $controller->getTagDistribution($user_id);

if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(500);
}

echo json_encode($result);
