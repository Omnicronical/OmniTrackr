<?php
/**
 * Feature: omnitrackr, Property 11: Activity display completeness
 * Validates: Requirements 5.3
 * 
 * Property: For any activity with category and tags, rendering the activity should 
 * include all key properties: title, description, category, and all associated tags.
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

// Generator: Creates random activity data with category and tags
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
    $category_name = $categoryData['name'];

    // Create some tags for the activity
    $tagController = new TagController($db);
    $tag_ids = [];
    $tag_names = [];
    $numTags = rand(1, 4);
    for ($i = 0; $i < $numTags; $i++) {
        $tagData = TestHelpers::randomTagData();
        $tagResult = $tagController->create($tagData, $user_id);
        $tag_ids[] = $tagResult['data']['id'];
        $tag_names[] = $tagData['name'];
    }

    // Generate random activity data
    $title = 'Activity_' . TestHelpers::randomString(10);
    $description = 'Description for activity ' . TestHelpers::randomString(20);
    
    $activityData = [
        'title' => $title,
        'description' => $description,
        'category_id' => $category_id,
        'tag_ids' => $tag_ids
    ];

    // Create the activity
    $activityController = new ActivityController($db);
    $createResult = $activityController->create($activityData, $user_id);
    
    if (!$createResult['success']) {
        throw new Exception("Failed to create activity: " . ($createResult['error']['message'] ?? 'Unknown error'));
    }

    $activity_id = $createResult['data']['id'];

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'activity_id' => $activity_id,
        'expected_title' => $title,
        'expected_description' => $description,
        'expected_category_name' => $category_name,
        'expected_tag_names' => $tag_names
    ];
};

// Property: Activity display should include all key properties
$property = function($data) use ($db) {
    $controller = new ActivityController($db);

    try {
        // Retrieve the activity (simulating what the frontend would receive)
        $getResult = $controller->get($data['activity_id'], $data['user_id']);

        PropertyTestRunner::assertTrue(
            $getResult['success'],
            "Failed to retrieve activity: " . ($getResult['error']['message'] ?? 'Unknown error')
        );

        $activity = $getResult['data'];

        // Verify title is present and correct
        PropertyTestRunner::assertNotNull(
            $activity['title'] ?? null,
            "Activity title should be present in display data"
        );
        
        PropertyTestRunner::assertEquals(
            $data['expected_title'],
            $activity['title'],
            "Activity title should match expected value"
        );

        // Verify description is present and correct
        PropertyTestRunner::assertNotNull(
            $activity['description'] ?? null,
            "Activity description should be present in display data"
        );
        
        PropertyTestRunner::assertEquals(
            $data['expected_description'],
            $activity['description'],
            "Activity description should match expected value"
        );

        // Verify category is present and correct
        PropertyTestRunner::assertNotNull(
            $activity['category_name'] ?? null,
            "Activity category name should be present in display data"
        );
        
        PropertyTestRunner::assertEquals(
            $data['expected_category_name'],
            $activity['category_name'],
            "Activity category name should match expected value"
        );

        // Verify all tags are present
        PropertyTestRunner::assertTrue(
            isset($activity['tags']) && is_array($activity['tags']),
            "Activity tags should be present as an array in display data"
        );

        PropertyTestRunner::assertEquals(
            count($data['expected_tag_names']),
            count($activity['tags']),
            "Activity should have all associated tags in display data"
        );

        // Verify each tag has a name
        $displayedTagNames = [];
        foreach ($activity['tags'] as $tag) {
            PropertyTestRunner::assertTrue(
                isset($tag['name']) && !empty($tag['name']),
                "Each tag in display data should have a name"
            );
            $displayedTagNames[] = $tag['name'];
        }

        // Verify all expected tag names are present
        sort($data['expected_tag_names']);
        sort($displayedTagNames);
        
        PropertyTestRunner::assertEquals(
            $data['expected_tag_names'],
            $displayedTagNames,
            "All expected tag names should be present in display data"
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
    "Property 11: Activity display completeness",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
