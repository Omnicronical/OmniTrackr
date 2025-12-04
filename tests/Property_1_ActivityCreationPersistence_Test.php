<?php
/**
 * Feature: omnitrackr, Property 1: Activity creation persistence
 * Validates: Requirements 1.1
 * 
 * Property: For any valid activity with required fields, creating the activity should 
 * result in it being retrievable from the database with all specified properties intact.
 */

require_once __DIR__ . '/PropertyTestRunner.php';
require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/ActivityController.php';
require_once __DIR__ . '/../src/controllers/CategoryController.php';
require_once __DIR__ . '/../src/controllers/TagController.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Creates random activity data with a test user
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Create a category for the activity
    $categoryController = new CategoryController($db);
    $categoryData = TestHelpers::randomCategoryData();
    $categoryResult = $categoryController->create($categoryData, $user_id);
    $category_id = $categoryResult['data']['id'];

    // Create some tags for the activity
    $tagController = new TagController($db);
    $tag_ids = [];
    $numTags = rand(1, 3);
    for ($i = 0; $i < $numTags; $i++) {
        $tagData = TestHelpers::randomTagData();
        $tagResult = $tagController->create($tagData, $user_id);
        $tag_ids[] = $tagResult['data']['id'];
    }

    // Generate random activity data
    $activityData = [
        'title' => 'Activity_' . TestHelpers::randomString(10),
        'description' => 'Description for activity ' . TestHelpers::randomString(20),
        'category_id' => $category_id,
        'tag_ids' => $tag_ids
    ];

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'activity_data' => $activityData
    ];
};

// Property: Created activity should be retrievable with all properties intact
$property = function($data) use ($db) {
    $controller = new ActivityController($db);

    try {
        // Create the activity
        $createResult = $controller->create($data['activity_data'], $data['user_id']);
        
        PropertyTestRunner::assertTrue(
            $createResult['success'],
            "Failed to create activity: " . ($createResult['error']['message'] ?? 'Unknown error')
        );

        $activity_id = $createResult['data']['id'];

        // Retrieve the activity
        $getResult = $controller->get($activity_id, $data['user_id']);

        PropertyTestRunner::assertTrue(
            $getResult['success'],
            "Failed to retrieve activity: " . ($getResult['error']['message'] ?? 'Unknown error')
        );

        $retrievedActivity = $getResult['data'];

        // Verify all properties are intact
        PropertyTestRunner::assertEquals(
            $data['activity_data']['title'],
            $retrievedActivity['title'],
            "Activity title does not match"
        );

        PropertyTestRunner::assertEquals(
            $data['activity_data']['description'],
            $retrievedActivity['description'],
            "Activity description does not match"
        );

        PropertyTestRunner::assertEquals(
            $data['activity_data']['category_id'],
            $retrievedActivity['category_id'],
            "Activity category_id does not match"
        );

        // Verify tag associations
        sort($data['activity_data']['tag_ids']);
        sort($retrievedActivity['tag_ids']);
        
        PropertyTestRunner::assertEquals(
            $data['activity_data']['tag_ids'],
            $retrievedActivity['tag_ids'],
            "Activity tag_ids do not match"
        );

        // Verify the activity has a valid ID
        PropertyTestRunner::assertTrue(
            is_int($retrievedActivity['id']) && $retrievedActivity['id'] > 0,
            "Activity ID should be a positive integer"
        );

        // Verify user_id matches
        PropertyTestRunner::assertEquals(
            $data['user_id'],
            $retrievedActivity['user_id'],
            "Activity user_id does not match"
        );

        return true;
    } finally {
        // Cleanup
        TestHelpers::cleanupUserActivities($db, $data['user_id']);
        TestHelpers::cleanupUserCategories($db, $data['user_id']);
        TestHelpers::cleanupUserTags($db, $data['user_id']);
        TestHelpers::cleanupUser($db, $data['username']);
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 1: Activity creation persistence",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
