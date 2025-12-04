<?php
/**
 * OmniTrackr Application Entry Point
 * 
 * This file serves as the main entry point for the application.
 * It handles routing and initializes the application.
 */

// Set error reporting based on environment
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session
session_start();

// Load database configuration
require_once __DIR__ . '/../src/config/database.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request URI and method
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$uri = parse_url($request_uri, PHP_URL_PATH);
$uri = trim($uri, '/');

// Basic routing (will be expanded in later tasks)
if ($uri === '' || $uri === 'index.php') {
    // Serve the frontend application
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmniTrackr - Activity Tracking</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div id="app">
        <h1>OmniTrackr</h1>
        <p>Activity tracking application - Setup complete!</p>
    </div>
    <script src="/js/app.js"></script>
</body>
</html>';
    exit();
}

// API routing (will be implemented in later tasks)
if (strpos($uri, 'api/') === 0) {
    // Test database connection
    if ($uri === 'api/health') {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $db->closeConnection();
            
            echo json_encode([
                'success' => true,
                'message' => 'Database connection successful',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'DB_CONNECTION_ERROR',
                    'message' => 'Database connection failed',
                    'details' => $e->getMessage()
                ]
            ]);
        }
        exit();
    }
    
    // Other API routes will be added in subsequent tasks
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'API endpoint not found',
            'details' => []
        ]
    ]);
    exit();
}

// 404 for other routes
http_response_code(404);
echo json_encode([
    'success' => false,
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'Route not found',
        'details' => []
    ]
]);
