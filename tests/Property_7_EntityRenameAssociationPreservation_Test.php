<?php
/**
 * Feature: omnitrackr, Property 7: Entity rename association preservation
 * Validates: Requirements 3.2, 4.2
 * 
 * Property: For any category or tag with associated activities, renaming the entity 
 * should update its name while maintaining all existing activity associations.
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

// Generator: Creates a user with a category and associated activities
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
    $categoryData = TestHelpers::randomCategoryData();
    $controller = new CategoryController($db);
    $result = $controller->create($categoryData, $user_id);
    $category_id = $result['data']['id'];

    // Create random number of activities (1-5) associated with this category
    $numActivities = rand(1, 5);
    $activity_ids = [];
    for ($i = 0; $i < $numActivities; $i++) {
        $stmt = $db->prepare("INSERT INTO activities (user_id, category_id, title, description) VALUES (?, ?, ?, ?)");
        $title = "Activity_" . TestHelpers::randomString(8);
        $description = "Description_" . TestHelpers::randomString(20);
        $stmt->bind_param("iiss", $user_id, $category_id, $title, $description);
        $stmt->execute();
        $activity_ids[] = $db->insert_id;
        $stmt->close();
    }

    // Generate a new name for the category
    $newName = TestHelpers::randomCategoryName();

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'category_id' => $category_id,
        'original_name' => $categoryData['name'],
        'new_name' => $newName,
        'activity_ids' => $activity_ids
    ];
};

// Property: Renaming a category should preserve all activity associations
$property = function($data) use ($db) {
    $controller = new CategoryController($db);

    try {
        // Count activities before rename
        $countBefore = TestHelpers::countActivitiesForCategory($db, $data['category_id']);
        PropertyTestRunner::assertEquals(
            count($data['activity_ids']),
            $countBefore,
            "Activity count before rename should match created activities"
        );

        // Rename the category
        $updateData = ['name' => $data['new_name']];
        $result = $controller->update($data['category_id'], $updateData, $data['user_id']);

        PropertyTestRunner::assertTrue(
            $result['success'],
            "Category rename should succeed"
        );

        PropertyTestRunner::assertEquals(
            $data['new_name'],
            $result['data']['name'],
            "Category name should be updated"
        );

        // Verify all activities still associated with the category
        $countAfter = TestHelpers::countActivitiesForCategory($db, $data['category_id']);
        PropertyTestRunner::assertEquals(
            $countBefore,
            $countAfter,
            "Activity count should remain the same after rename"
        );

        // Verify each activity still has the correct category_id
        foreach ($data['activity_ids'] as $activity_id) {
            $stmt = $db->prepare("SELECT category_id FROM activities WHERE id = ?");
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $activity = $result->fetch_assoc();
            $stmt->close();

            PropertyTestRunner::assertEquals(
                $data['category_id'],
                $activity['category_id'],
                "Activity should still be associated with the renamed category"
            );
        }

        // Verify the category name was actually changed in the database
        $category = TestHelpers::getCategoryById($db, $data['category_id']);
        PropertyTestRunner::assertEquals(
            $data['new_name'],
            $category['name'],
            "Category name should be updated in database"
        );

        return true;
    } finally {
        // Cleanup
        TestHelpers::cleanupUserActivities($db, $data['user_id']);
        TestHelpers::cleanupUserCategories($db, $data['user_id']);
        TestHelpers::cleanupUser($db, $data['username']);
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 7: Entity rename association preservation",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
