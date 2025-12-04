<?php
/**
 * User Logout Endpoint
 * POST /api/auth/logout
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    exit;
}

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/controllers/AuthController.php';

try {
    // Start PHP session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get session ID from PHP session or cookie
    $session_id = null;
    
    if (isset($_SESSION['session_id'])) {
        $session_id = $_SESSION['session_id'];
    } elseif (isset($_COOKIE['session_id'])) {
        $session_id = $_COOKIE['session_id'];
    }

    // Create auth controller and logout user
    $authController = new AuthController();
    $result = $authController->logout($session_id);

    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(200);
        
        // Clear session cookie
        setcookie('session_id', '', time() - 3600, '/');
        
        // Destroy PHP session
        session_unset();
        session_destroy();
    } else {
        if (isset($result['error']['code'])) {
            switch ($result['error']['code']) {
                case 'VALIDATION_ERROR':
                case 'INVALID_SESSION':
                    http_response_code(401);
                    break;
                default:
                    http_response_code(500);
            }
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
            'message' => 'An unexpected error occurred',
            'details' => []
        ]
    ]);
}
