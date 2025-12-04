<?php
/**
 * User Login Endpoint
 * POST /api/auth/login
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);

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
        exit;
    }

    // Create auth controller and login user
    $authController = new AuthController();
    $result = $authController->login($data);

    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(200);
        
        // Set session cookie
        if (isset($result['data']['session_id'])) {
            $session_lifetime = getenv('SESSION_LIFETIME') ?: 86400;
            setcookie(
                'session_id',
                $result['data']['session_id'],
                time() + $session_lifetime,
                '/',
                '',
                false, // Set to true in production with HTTPS
                true   // httpOnly
            );
            
            // Start PHP session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $result['data']['user_id'];
            $_SESSION['session_id'] = $result['data']['session_id'];
        }
        
        // Return formatted response
        echo json_encode([
            'success' => true,
            'session_id' => $result['data']['session_id'],
            'user' => [
                'id' => $result['data']['user_id'],
                'username' => $result['data']['username'],
                'email' => $result['data']['email']
            ]
        ]);
    } else {
        if (isset($result['error']['code'])) {
            switch ($result['error']['code']) {
                case 'VALIDATION_ERROR':
                case 'INVALID_CREDENTIALS':
                    http_response_code(401);
                    break;
                default:
                    http_response_code(500);
            }
        } else {
            http_response_code(500);
        }
        echo json_encode($result);
    }

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
