<?php
/**
 * Router for PHP Built-in Server
 * This file handles routing when using php -S
 */

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If it's a static file that exists (css, js, images, etc), serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    // Check if it's not a PHP file
    if (!preg_match('/\.php$/', $uri)) {
        return false; // Let PHP serve the file as-is
    }
}

// If it's an API request, try to find and execute the PHP file
if (preg_match('/^\/api\//', $uri)) {
    $file = __DIR__ . $uri;
    
    // If the URI doesn't end with .php, try adding it
    if (!file_exists($file)) {
        $file = $file . '.php';
    }
    
    if (file_exists($file) && is_file($file)) {
        require $file;
        exit;
    }
    
    // If we get here, the API endpoint doesn't exist
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'API endpoint not found',
            'details' => ['uri' => $uri]
        ]
    ]);
    exit;
}

// For all other requests, use index.php
require __DIR__ . '/index.php';
