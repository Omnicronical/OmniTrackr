<?php
/**
 * Feature: omnitrackr, Property 3: Optional field defaults
 * Validates: Requirements 1.4
 * 
 * Property: For any activity created without optional fields, the system should assign 
 * default values that can be retrieved.
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

// Generator: Creates activity data with various combinations of optional fields omitted
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Randomly decide which optional fields to omit
    $omitDescription = (rand(0, 1) === 1);
    $omitCategory = (rand(0, 1) === 1);
    $omitTags = (rand(0, 1) === 1);

    // Build activity data with only required fields and some optional fields
    $activityData = [
        'title' => 'Activity_' . TestHelpers::randomString(10)
    ];

    // Optionally add description
    if (!$omitDescription) {
        $activityData['description'] = 'Description ' . TestHelpers::randomString(20);
    }

    // Optionally add category
    $category_id = null;
    if (!$omitCategory) {
        $categoryController = new CategoryController($db);
        $categoryData = TestHelpers::randomCategoryData();
        $categoryResult = $categoryController->create($categoryData, $user_id);
        $category_id = $categoryResult['data']['id'];
        $activityData['category_id'] = $category_id;
    }

    // Optionally add tags
    $tag_ids = [];
    if (!$omitTags) {
        $tagController = new TagController($db);
        $numTags = rand(1, 3);
        for ($i = 0; $i < $numTags; $i++) {
            $tagData = TestHelpers::randomTagData();
            $tagResult = $tagController->create($tagData, $user_id);
            $tag_ids[] = $tagResult['data']['id'];
        }
        $activityData['tag_ids'] = $tag_ids;
    }

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'activity_data' => $activityData,
        'omitted_description' => $omitDescription,
        'omitted_category' => $omitCategory,
        'omitted_tags' => $omitTags
    ];
};

// Property: Activities with omitted optional fields should have default values
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
            "Failed to retrieve activity"
        );

        $retrievedActivity = $getResult['data'];

        // Verify title is always present (required field)
        PropertyTestRunner::assertNotNull(
            $retrievedActivity['title'],
            "Title should not be null"
        );

        // If description was omitted, it should default to empty string
        if ($data['omitted_description']) {
            PropertyTestRunner::assertTrue(
                isset($retrievedActivity['description']),
                "Description field should exist even when omitted"
            );
            PropertyTestRunner::assertEquals(
                '',
                $retrievedActivity['description'],
                "Omitted description should default to empty string"
            );
        } else {
            PropertyTestRunner::assertEquals(
                $data['activity_data']['description'],
                $retrievedActivity['description'],
                "Provided description should be preserved"
            );
        }

        // If category was omitted, it should be null
        if ($data['omitted_category']) {
            PropertyTestRunner::assertNull(
                $retrievedActivity['category_id'],
                "Omitted category should be null"
            );
        } else {
            PropertyTestRunner::assertEquals(
                $data['activity_data']['category_id'],
                $retrievedActivity['category_id'],
                "Provided category should be preserved"
            );
        }

        // If tags were omitted, it should be an empty array
        if ($data['omitted_tags']) {
            PropertyTestRunner::assertTrue(
                is_array($retrievedActivity['tag_ids']),
                "Tag IDs should be an array even when omitted"
            );
            PropertyTestRunner::assertEquals(
                0,
                count($retrievedActivity['tag_ids']),
                "Omitted tags should result in empty array"
            );
        } else {
            sort($data['activity_data']['tag_ids']);
            sort($retrievedActivity['tag_ids']);
            PropertyTestRunner::assertEquals(
                $data['activity_data']['tag_ids'],
                $retrievedActivity['tag_ids'],
                "Provided tags should be preserved"
            );
        }

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
    "Property 3: Optional field defaults",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
