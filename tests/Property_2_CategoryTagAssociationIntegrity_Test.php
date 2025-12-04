<?php
/**
 * Feature: omnitrackr, Property 2: Category and tag association integrity
 * Validates: Requirements 1.2, 1.3
 * 
 * Property: For any activity, category, and set of tags, assigning them to the activity 
 * should result in all associations being correctly stored and retrievable.
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

// Generator: Creates activity with random category and tags
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Create multiple categories
    $categoryController = new CategoryController($db);
    $categories = [];
    $numCategories = rand(2, 5);
    for ($i = 0; $i < $numCategories; $i++) {
        $categoryData = TestHelpers::randomCategoryData();
        $categoryResult = $categoryController->create($categoryData, $user_id);
        $categories[] = $categoryResult['data'];
    }

    // Create multiple tags
    $tagController = new TagController($db);
    $tags = [];
    $numTags = rand(3, 7);
    for ($i = 0; $i < $numTags; $i++) {
        $tagData = TestHelpers::randomTagData();
        $tagResult = $tagController->create($tagData, $user_id);
        $tags[] = $tagResult['data'];
    }

    // Select random category and random subset of tags
    $selectedCategory = $categories[array_rand($categories)];
    $numSelectedTags = rand(1, count($tags));
    $selectedTagIds = array_slice(array_column($tags, 'id'), 0, $numSelectedTags);

    // Generate activity data
    $activityData = [
        'title' => 'Activity_' . TestHelpers::randomString(10),
        'description' => 'Description ' . TestHelpers::randomString(20),
        'category_id' => $selectedCategory['id'],
        'tag_ids' => $selectedTagIds
    ];

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'activity_data' => $activityData,
        'expected_category_id' => $selectedCategory['id'],
        'expected_tag_ids' => $selectedTagIds
    ];
};

// Property: Category and tag associations should be correctly stored and retrievable
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

        // Verify category association
        PropertyTestRunner::assertEquals(
            $data['expected_category_id'],
            $retrievedActivity['category_id'],
            "Category association not preserved"
        );

        // Verify tag associations
        sort($data['expected_tag_ids']);
        sort($retrievedActivity['tag_ids']);
        
        PropertyTestRunner::assertEquals(
            $data['expected_tag_ids'],
            $retrievedActivity['tag_ids'],
            "Tag associations not preserved"
        );

        // Verify all expected tags are present
        PropertyTestRunner::assertEquals(
            count($data['expected_tag_ids']),
            count($retrievedActivity['tag_ids']),
            "Number of tags does not match"
        );

        // Verify no extra tags were added
        foreach ($retrievedActivity['tag_ids'] as $tag_id) {
            PropertyTestRunner::assertTrue(
                in_array($tag_id, $data['expected_tag_ids']),
                "Unexpected tag ID found: " . $tag_id
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
    "Property 2: Category and tag association integrity",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
