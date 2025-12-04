<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register
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

    // Create auth controller and register user
    $authController = new AuthController();
    $result = $authController->register($data);

    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(201);
        
        // Auto-login after registration
        $loginResult = $authController->login([
            'username' => $data['username'],
            'password' => $data['password']
        ]);
        
        if ($loginResult['success']) {
            // Set session cookie
            if (isset($loginResult['data']['session_id'])) {
                $session_lifetime = getenv('SESSION_LIFETIME') ?: 86400;
                setcookie(
                    'session_id',
                    $loginResult['data']['session_id'],
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
                $_SESSION['user_id'] = $loginResult['data']['user_id'];
                $_SESSION['session_id'] = $loginResult['data']['session_id'];
            }
            
            // Return combined result
            echo json_encode([
                'success' => true,
                'session_id' => $loginResult['data']['session_id'],
                'user' => [
                    'id' => $loginResult['data']['user_id'],
                    'username' => $loginResult['data']['username'],
                    'email' => $loginResult['data']['email']
                ]
            ]);
        } else {
            // Registration succeeded but login failed
            echo json_encode($result);
        }
    } else {
        if (isset($result['error']['code'])) {
            switch ($result['error']['code']) {
                case 'VALIDATION_ERROR':
                case 'DUPLICATE_USERNAME':
                case 'DUPLICATE_EMAIL':
                    http_response_code(400);
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
