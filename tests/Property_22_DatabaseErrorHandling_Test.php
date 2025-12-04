<?php
/**
 * Feature: omnitrackr, Property 22: Database error handling
 * Validates: Requirements 12.4
 * 
 * Property: For any database operation failure, the system should handle the error 
 * gracefully and return an appropriate error response without crashing.
 */

require_once __DIR__ . '/PropertyTestRunner.php';
require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/ActivityController.php';
require_once __DIR__ . '/../src/controllers/CategoryController.php';
require_once __DIR__ . '/../src/controllers/TagController.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';

/**
 * Mock database connection that simulates failures
 */
class MockFailingDatabase {
    private $realDb;
    
    public function __construct($realDb) {
        $this->realDb = $realDb;
    }
    
    public function prepare($query) {
        // Always return false to simulate prepare failure
        return false;
    }
    
    public function __get($name) {
        return $this->realDb->$name;
    }
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Creates test scenarios with failing database connections
$generator = function() use ($db) {
    // Create a test user first with real DB
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Create a mock failing database
    $mockDb = new MockFailingDatabase($db);

    // Randomly select an operation to test
    $operations = ['create_activity', 'get_activity', 'create_category', 'create_tag'];
    $operation = $operations[array_rand($operations)];

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'mock_db' => $mockDb,
        'operation' => $operation
    ];
};

// Property: Database failures should be handled gracefully
$property = function($data) use ($db) {
    try {
        $mockDb = $data['mock_db'];
        $operation = $data['operation'];
        $result = null;

        // Test different operations with failing database
        switch ($operation) {
            case 'create_activity':
                $controller = new ActivityController($mockDb);
                $activityData = [
                    'title' => 'Test Activity',
                    'description' => 'Test Description'
                ];
                $result = $controller->create($activityData, $data['user_id']);
                break;

            case 'get_activity':
                $controller = new ActivityController($mockDb);
                $result = $controller->get(1, $data['user_id']);
                break;

            case 'create_category':
                $controller = new CategoryController($mockDb);
                $categoryData = ['name' => 'Test Category'];
                $result = $controller->create($categoryData, $data['user_id']);
                break;

            case 'create_tag':
                $controller = new TagController($mockDb);
                $tagData = ['name' => 'Test Tag'];
                $result = $controller->create($tagData, $data['user_id']);
                break;
        }

        // Verify the result is an array with proper structure
        PropertyTestRunner::assertTrue(
            is_array($result),
            "Result should be an array, got: " . gettype($result)
        );

        // Verify the result has a 'success' key
        PropertyTestRunner::assertTrue(
            isset($result['success']),
            "Result should have 'success' key"
        );

        // Verify the operation failed (as expected with mock DB)
        PropertyTestRunner::assertTrue(
            $result['success'] === false,
            "Operation should fail with mock failing database"
        );

        // Verify error structure exists
        PropertyTestRunner::assertTrue(
            isset($result['error']),
            "Failed result should have 'error' key"
        );

        PropertyTestRunner::assertTrue(
            is_array($result['error']),
            "Error should be an array"
        );

        // Verify error has required fields
        PropertyTestRunner::assertTrue(
            isset($result['error']['code']),
            "Error should have 'code' field"
        );

        PropertyTestRunner::assertTrue(
            isset($result['error']['message']),
            "Error should have 'message' field"
        );

        PropertyTestRunner::assertTrue(
            isset($result['error']['details']),
            "Error should have 'details' field"
        );

        // Verify error code is appropriate (DATABASE_ERROR is expected for DB failures)
        PropertyTestRunner::assertTrue(
            in_array($result['error']['code'], ['DATABASE_ERROR', 'NOT_FOUND', 'VALIDATION_ERROR']),
            "Error code should be DATABASE_ERROR, NOT_FOUND, or VALIDATION_ERROR, got: " . $result['error']['code']
        );

        // Verify error message is not empty
        PropertyTestRunner::assertTrue(
            !empty($result['error']['message']),
            "Error message should not be empty"
        );

        // Verify details is an array
        PropertyTestRunner::assertTrue(
            is_array($result['error']['details']),
            "Error details should be an array"
        );

        return true;
    } finally {
        // Cleanup
        TestHelpers::cleanupUser($db, $data['username']);
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 22: Database error handling",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
