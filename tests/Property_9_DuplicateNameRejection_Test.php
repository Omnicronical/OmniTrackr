<?php
/**
 * Feature: omnitrackr, Property 9: Duplicate name rejection
 * Validates: Requirements 3.4, 4.4
 * 
 * Property: For any user with an existing category or tag name, attempting to create 
 * another entity with the same name should be rejected with an error.
 */

require_once __DIR__ . '/PropertyTestRunner.php';
require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/CategoryController.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Creates a user with an existing category
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Create an initial category
    $categoryData = TestHelpers::randomCategoryData();
    $controller = new CategoryController($db);
    $result = $controller->create($categoryData, $user_id);

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'existing_category_name' => $categoryData['name'],
        'category_id' => $result['data']['id']
    ];
};

// Property: Attempting to create a category with duplicate name should be rejected
$property = function($data) use ($db) {
    $controller = new CategoryController($db);

    try {
        // Attempt to create another category with the same name
        $duplicateData = [
            'name' => $data['existing_category_name'],
            'color' => TestHelpers::randomColor()
        ];

        $result = $controller->create($duplicateData, $data['user_id']);

        // Verify that creation failed
        PropertyTestRunner::assertTrue(
            !$result['success'],
            "Creating category with duplicate name should fail"
        );

        // Verify error code is DUPLICATE_NAME
        PropertyTestRunner::assertEquals(
            'DUPLICATE_NAME',
            $result['error']['code'],
            "Error code should be DUPLICATE_NAME"
        );

        // Verify that only one category with this name exists
        $categories = TestHelpers::getCategoriesByUser($db, $data['user_id']);
        $matchingCategories = array_filter($categories, function($cat) use ($data) {
            return $cat['name'] === $data['existing_category_name'];
        });

        PropertyTestRunner::assertEquals(
            1,
            count($matchingCategories),
            "Only one category with the name should exist"
        );

        return true;
    } finally {
        // Cleanup
        TestHelpers::cleanupUserCategories($db, $data['user_id']);
        TestHelpers::cleanupUser($db, $data['username']);
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 9: Duplicate name rejection",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
