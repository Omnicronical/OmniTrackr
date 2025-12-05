---
inclusion: always
---

# API Endpoint Standards

This document defines the mandatory standards for all API endpoints in this project to ensure consistency, security, and proper error handling.

## Mandatory Requirements for ALL API Endpoints

### 1. Error Suppression (REQUIRED)
Every API endpoint MUST start with error suppression to prevent exposing internal errors to clients:

```php
<?php
// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);
```

### 2. Try-Catch Block (REQUIRED)
ALL endpoint logic MUST be wrapped in a try-catch block:

```php
try {
    // All endpoint logic here
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
```

### 3. Authentication Pattern (REQUIRED)
Use the INSTANCE method pattern for authentication (NOT static calls):

```php
// CORRECT - Instance method
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

$user_id = $user['user_id']; // Use 'user_id' key, NOT 'id'
```

**WRONG - Do NOT use:**
```php
// WRONG - Static call (doesn't exist)
$auth = AuthMiddleware::authenticate();

// WRONG - Incorrect key
$user_id = $user['id']; // Should be $user['user_id']
```

### 4. Database Initialization (REQUIRED)
Always initialize database connection before passing to controllers:

```php
// CORRECT - Initialize database
$database = new Database();
$db = $database->getConnection();
$controller = new SomeController($db);

// WRONG - Missing database initialization
$controller = new SomeController(); // May fail if controller expects $db
```

### 5. Standard Response Format (REQUIRED)
All responses MUST follow this format:

```php
// Success response
[
    'success' => true,
    'data' => [/* response data */]
]

// Error response
[
    'success' => false,
    'error' => [
        'code' => 'ERROR_CODE',
        'message' => 'Human readable message',
        'details' => []  // Never expose sensitive internal details
    ]
]
```

## Complete Endpoint Template

Use this template for ALL new API endpoints:

```php
<?php
/**
 * [Endpoint Description]
 * [METHOD] /api/[resource]/[action].php
 */

// Disable error display for API endpoints
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: [GET|POST|PUT|DELETE]');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../../src/config/database.php';
    require_once __DIR__ . '/../../../src/controllers/[Controller].php';
    require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';

    // Only allow specific method
    if ($_SERVER['REQUEST_METHOD'] !== '[METHOD]') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only [METHOD] requests are allowed',
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

    // [Endpoint-specific logic here]
    
    // Initialize database and controller
    $database = new Database();
    $db = $database->getConnection();
    $controller = new [Controller]($db);
    
    // Call controller method
    $result = $controller->[method]($user_id, /* other params */);

    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(200); // or 201 for create
    } else {
        // Map error codes to HTTP status codes
        $status_code = 400;
        if (isset($result['error']['code'])) {
            switch ($result['error']['code']) {
                case 'UNAUTHORIZED':
                    $status_code = 401;
                    break;
                case 'FORBIDDEN':
                    $status_code = 403;
                    break;
                case 'NOT_FOUND':
                    $status_code = 404;
                    break;
                case 'DUPLICATE_NAME':
                    $status_code = 409;
                    break;
                case 'VALIDATION_ERROR':
                    $status_code = 400;
                    break;
                case 'SERVER_ERROR':
                case 'DATABASE_ERROR':
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
            'details' => []
        ]
    ]);
}
```

## Reference Implementation

See these endpoints as reference implementations:
- `public/api/activities/create.php`
- `public/api/activities/list.php`
- `public/api/activities/update.php`
- `public/api/activities/delete.php`

## Common Mistakes to AVOID

1. ❌ Missing `ini_set('display_errors', '0')` at the top
2. ❌ No try-catch block wrapping the logic
3. ❌ Using `AuthMiddleware::authenticate()` (static call)
4. ❌ Using `$user['id']` instead of `$user['user_id']`
5. ❌ Not initializing database before passing to controller
6. ❌ Exposing internal error details in catch blocks
7. ❌ Inconsistent error response format

## Checklist for New Endpoints

Before committing any new API endpoint, verify:

- [ ] Error suppression is enabled at the top
- [ ] All logic is wrapped in try-catch
- [ ] Authentication uses instance method pattern
- [ ] Uses `$user['user_id']` (not `$user['id']`)
- [ ] Database is initialized before controller
- [ ] Generic error messages in catch block
- [ ] Consistent response format
- [ ] Appropriate HTTP status codes
- [ ] Preflight OPTIONS handling

## Testing

After creating an endpoint, test:
1. Valid authenticated request
2. Missing authentication
3. Invalid JSON (for POST/PUT)
4. Database connection failure
5. Invalid method (e.g., GET on POST endpoint)
