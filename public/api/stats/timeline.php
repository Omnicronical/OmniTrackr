<?php
/**
 * Get Timeline Data
 * 
 * GET /api/stats/timeline?days=30
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

// Get days parameter (default 30)
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Validate days parameter
if ($days < 1 || $days > 365) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Days parameter must be between 1 and 365',
            'details' => []
        ]
    ]);
    exit;
}

// Get timeline data
$controller = new StatsController();
$result = $controller->getTimeline($user_id, $days);

if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(500);
}

echo json_encode($result);
