<?php
/**
 * Feature: omnitrackr, Property 6: Entity creation uniqueness
 * Validates: Requirements 1.5, 3.1, 4.1
 * 
 * Property: For any user, creating multiple entities (categories or tags) should result 
 * in each having a unique identifier within that user's scope.
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

// Generator: Creates random number of categories for a test user
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Generate random number of categories (2-10)
    $numCategories = rand(2, 10);
    $categoryData = [];
    for ($i = 0; $i < $numCategories; $i++) {
        $categoryData[] = TestHelpers::randomCategoryData();
    }

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'categories' => $categoryData
    ];
};

// Property: All created entities should have unique IDs
$property = function($data) use ($db) {
    $controller = new CategoryController($db);
    $createdIds = [];

    try {
        // Create all categories
        foreach ($data['categories'] as $categoryData) {
            $result = $controller->create($categoryData, $data['user_id']);
            
            PropertyTestRunner::assertTrue(
                $result['success'],
                "Failed to create category: " . ($result['error']['message'] ?? 'Unknown error')
            );

            $createdIds[] = $result['data']['id'];
        }

        // Check that all IDs are unique
        $uniqueIds = array_unique($createdIds);
        PropertyTestRunner::assertEquals(
            count($createdIds),
            count($uniqueIds),
            "Not all category IDs are unique. Created: " . count($createdIds) . ", Unique: " . count($uniqueIds)
        );

        // Verify each ID is a positive integer
        foreach ($createdIds as $id) {
            PropertyTestRunner::assertTrue(
                is_int($id) && $id > 0,
                "Category ID should be a positive integer, got: " . $id
            );
        }

        return true;
    } finally {
        // Cleanup
        TestHelpers::cleanupUserCategories($db, $data['user_id']);
        TestHelpers::cleanupUser($db, $data['username']);
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 6: Entity creation uniqueness",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
