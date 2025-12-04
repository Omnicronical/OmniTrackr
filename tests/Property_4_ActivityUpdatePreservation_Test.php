<?php
/**
 * Feature: omnitrackr, Property 4: Activity update preservation
 * Validates: Requirements 2.1, 2.4
 * 
 * Property: For any activity and any valid property modifications, updating the activity 
 * should persist the new values while maintaining the activity's identity and unmodified properties.
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

// Generator: Creates activity and generates update data
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    // Create categories
    $categoryController = new CategoryController($db);
    $categories = [];
    for ($i = 0; $i < 3; $i++) {
        $categoryData = TestHelpers::randomCategoryData();
        $categoryResult = $categoryController->create($categoryData, $user_id);
        $categories[] = $categoryResult['data']['id'];
    }

    // Create tags
    $tagController = new TagController($db);
    $tags = [];
    for ($i = 0; $i < 5; $i++) {
        $tagData = TestHelpers::randomTagData();
        $tagResult = $tagController->create($tagData, $user_id);
        $tags[] = $tagResult['data']['id'];
    }

    // Create initial activity
    $initialActivity = [
        'title' => 'Initial_' . TestHelpers::randomString(10),
        'description' => 'Initial description ' . TestHelpers::randomString(20),
        'category_id' => $categories[0],
        'tag_ids' => array_slice($tags, 0, 2)
    ];

    // Generate update data (randomly update some fields)
    $updateData = [];
    
    // Randomly update title
    if (rand(0, 1) === 1) {
        $updateData['title'] = 'Updated_' . TestHelpers::randomString(10);
    }
    
    // Randomly update description
    if (rand(0, 1) === 1) {
        $updateData['description'] = 'Updated description ' . TestHelpers::randomString(20);
    }
    
    // Randomly update category
    if (rand(0, 1) === 1) {
        $updateData['category_id'] = $categories[1];
    }
    
    // Randomly update tags
    if (rand(0, 1) === 1) {
        $updateData['tag_ids'] = array_slice($tags, 2, 3);
    }

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'initial_activity' => $initialActivity,
        'update_data' => $updateData
    ];
};

// Property: Updated activity should have new values while preserving identity and unmodified properties
$property = function($data) use ($db) {
    $controller = new ActivityController($db);

    try {
        // Create the initial activity
        $createResult = $controller->create($data['initial_activity'], $data['user_id']);
        
        PropertyTestRunner::assertTrue(
            $createResult['success'],
            "Failed to create activity: " . ($createResult['error']['message'] ?? 'Unknown error')
        );

        $activity_id = $createResult['data']['id'];
        $original_user_id = $createResult['data']['user_id'];

        // Update the activity
        $updateResult = $controller->update($activity_id, $data['update_data'], $data['user_id']);

        PropertyTestRunner::assertTrue(
            $updateResult['success'],
            "Failed to update activity: " . ($updateResult['error']['message'] ?? 'Unknown error')
        );

        // Retrieve the updated activity
        $getResult = $controller->get($activity_id, $data['user_id']);

        PropertyTestRunner::assertTrue(
            $getResult['success'],
            "Failed to retrieve updated activity"
        );

        $updatedActivity = $getResult['data'];

        // Verify identity is preserved (ID and user_id should not change)
        PropertyTestRunner::assertEquals(
            $activity_id,
            $updatedActivity['id'],
            "Activity ID should not change after update"
        );

        PropertyTestRunner::assertEquals(
            $original_user_id,
            $updatedActivity['user_id'],
            "User ID should not change after update"
        );

        // Verify updated fields have new values
        if (isset($data['update_data']['title'])) {
            PropertyTestRunner::assertEquals(
                $data['update_data']['title'],
                $updatedActivity['title'],
                "Title should be updated"
            );
        } else {
            PropertyTestRunner::assertEquals(
                $data['initial_activity']['title'],
                $updatedActivity['title'],
                "Title should be preserved when not updated"
            );
        }

        if (isset($data['update_data']['description'])) {
            PropertyTestRunner::assertEquals(
                $data['update_data']['description'],
                $updatedActivity['description'],
                "Description should be updated"
            );
        } else {
            PropertyTestRunner::assertEquals(
                $data['initial_activity']['description'],
                $updatedActivity['description'],
                "Description should be preserved when not updated"
            );
        }

        if (isset($data['update_data']['category_id'])) {
            PropertyTestRunner::assertEquals(
                $data['update_data']['category_id'],
                $updatedActivity['category_id'],
                "Category should be updated"
            );
        } else {
            PropertyTestRunner::assertEquals(
                $data['initial_activity']['category_id'],
                $updatedActivity['category_id'],
                "Category should be preserved when not updated"
            );
        }

        if (isset($data['update_data']['tag_ids'])) {
            sort($data['update_data']['tag_ids']);
            sort($updatedActivity['tag_ids']);
            PropertyTestRunner::assertEquals(
                $data['update_data']['tag_ids'],
                $updatedActivity['tag_ids'],
                "Tags should be updated"
            );
        } else {
            sort($data['initial_activity']['tag_ids']);
            sort($updatedActivity['tag_ids']);
            PropertyTestRunner::assertEquals(
                $data['initial_activity']['tag_ids'],
                $updatedActivity['tag_ids'],
                "Tags should be preserved when not updated"
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
    "Property 4: Activity update preservation",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
