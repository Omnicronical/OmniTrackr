<?php
/**
 * Feature: omnitrackr, Property 8: Entity deletion cascade
 * Validates: Requirements 3.3, 4.3
 * 
 * Property: For any category or tag with associated activities, deleting the entity 
 * should remove it from the database and appropriately update all associated activities.
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

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'category_id' => $category_id,
        'category_name' => $categoryData['name'],
        'activity_ids' => $activity_ids
    ];
};

// Property: Deleting a category should remove it and set category_id to NULL in activities
$property = function($data) use ($db) {
    $controller = new CategoryController($db);

    try {
        // Verify category exists before deletion
        $categoryBefore = TestHelpers::getCategoryById($db, $data['category_id']);
        PropertyTestRunner::assertNotNull(
            $categoryBefore,
            "Category should exist before deletion"
        );

        // Verify activities are associated with the category
        $countBefore = TestHelpers::countActivitiesForCategory($db, $data['category_id']);
        PropertyTestRunner::assertEquals(
            count($data['activity_ids']),
            $countBefore,
            "Activity count should match created activities"
        );

        // Delete the category
        $result = $controller->delete($data['category_id'], $data['user_id']);

        PropertyTestRunner::assertTrue(
            $result['success'],
            "Category deletion should succeed"
        );

        // Verify category no longer exists
        $categoryAfter = TestHelpers::getCategoryById($db, $data['category_id']);
        PropertyTestRunner::assertNull(
            $categoryAfter,
            "Category should not exist after deletion"
        );

        // Verify activities still exist but with category_id set to NULL
        // (based on the schema: FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL)
        foreach ($data['activity_ids'] as $activity_id) {
            $stmt = $db->prepare("SELECT id, category_id FROM activities WHERE id = ?");
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $activity = $result->fetch_assoc();
            $stmt->close();

            PropertyTestRunner::assertNotNull(
                $activity,
                "Activity should still exist after category deletion"
            );

            PropertyTestRunner::assertNull(
                $activity['category_id'],
                "Activity's category_id should be NULL after category deletion"
            );
        }

        // Verify no activities are associated with the deleted category
        $countAfter = TestHelpers::countActivitiesForCategory($db, $data['category_id']);
        PropertyTestRunner::assertEquals(
            0,
            $countAfter,
            "No activities should be associated with deleted category"
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
    "Property 8: Entity deletion cascade",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
