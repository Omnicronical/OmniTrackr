<?php
/**
 * Feature: omnitrackr, Property 5: Activity deletion cascade
 * Validates: Requirements 2.2
 * 
 * Property: For any activity with associations, deleting the activity should remove it 
 * and all its associations from the database, with no orphaned references remaining.
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

// Generator: Creates activity with category and tags
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Create a category
    $categoryController = new CategoryController($db);
    $categoryData = TestHelpers::randomCategoryData();
    $categoryResult = $categoryController->create($categoryData, $user_id);
    $category_id = $categoryResult['data']['id'];

    // Create multiple tags
    $tagController = new TagController($db);
    $tag_ids = [];
    $numTags = rand(2, 5);
    for ($i = 0; $i < $numTags; $i++) {
        $tagData = TestHelpers::randomTagData();
        $tagResult = $tagController->create($tagData, $user_id);
        $tag_ids[] = $tagResult['data']['id'];
    }

    // Create activity with category and tags
    $activityData = [
        'title' => 'Activity_' . TestHelpers::randomString(10),
        'description' => 'Description ' . TestHelpers::randomString(20),
        'category_id' => $category_id,
        'tag_ids' => $tag_ids
    ];

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'activity_data' => $activityData,
        'category_id' => $category_id,
        'tag_ids' => $tag_ids
    ];
};

// Property: Deleted activity should be removed along with all associations, no orphaned references
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

        // Verify activity exists before deletion
        $getBeforeResult = $controller->get($activity_id, $data['user_id']);
        PropertyTestRunner::assertTrue(
            $getBeforeResult['success'],
            "Activity should exist before deletion"
        );

        // Verify tag associations exist before deletion
        $tagsBefore = TestHelpers::getActivityTags($db, $activity_id);
        PropertyTestRunner::assertEquals(
            count($data['tag_ids']),
            count($tagsBefore),
            "Tag associations should exist before deletion"
        );

        // Delete the activity
        $deleteResult = $controller->delete($activity_id, $data['user_id']);

        PropertyTestRunner::assertTrue(
            $deleteResult['success'],
            "Failed to delete activity: " . ($deleteResult['error']['message'] ?? 'Unknown error')
        );

        // Verify activity no longer exists
        $getAfterResult = $controller->get($activity_id, $data['user_id']);
        PropertyTestRunner::assertTrue(
            !$getAfterResult['success'],
            "Activity should not exist after deletion"
        );

        PropertyTestRunner::assertEquals(
            'NOT_FOUND',
            $getAfterResult['error']['code'] ?? '',
            "Should return NOT_FOUND error for deleted activity"
        );

        // Verify activity is removed from database
        $activityInDb = TestHelpers::getActivityById($db, $activity_id);
        PropertyTestRunner::assertNull(
            $activityInDb,
            "Activity should be removed from database"
        );

        // Verify tag associations are removed (no orphaned references)
        $tagsAfter = TestHelpers::getActivityTags($db, $activity_id);
        PropertyTestRunner::assertEquals(
            0,
            count($tagsAfter),
            "Tag associations should be removed (no orphaned references)"
        );

        // Verify category still exists (should not be deleted)
        $categoryStillExists = TestHelpers::getCategoryById($db, $data['category_id']);
        PropertyTestRunner::assertNotNull(
            $categoryStillExists,
            "Category should still exist after activity deletion"
        );

        // Verify tags still exist (should not be deleted)
        foreach ($data['tag_ids'] as $tag_id) {
            $stmt = $db->prepare("SELECT id FROM tags WHERE id = ?");
            $stmt->bind_param("i", $tag_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $tagExists = $result->fetch_assoc();
            $stmt->close();
            
            PropertyTestRunner::assertNotNull(
                $tagExists,
                "Tag $tag_id should still exist after activity deletion"
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
    "Property 5: Activity deletion cascade",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
