<?php
/**
 * Feature: omnitrackr, Property 13: Filter clear restoration
 * Validates: Requirements 6.4
 * 
 * Property: For any set of activities, applying filters then clearing all filters
 * should return the complete original set of activities.
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

// Generator: Creates a user with multiple activities, categories, and tags
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
    $numCategories = rand(2, 4);
    for ($i = 0; $i < $numCategories; $i++) {
        $categoryData = TestHelpers::randomCategoryData();
        $categoryResult = $categoryController->create($categoryData, $user_id);
        $categories[] = $categoryResult['data']['id'];
    }

    // Create multiple tags
    $tagController = new TagController($db);
    $tags = [];
    $numTags = rand(3, 5);
    for ($i = 0; $i < $numTags; $i++) {
        $tagData = TestHelpers::randomTagData();
        $tagResult = $tagController->create($tagData, $user_id);
        $tags[] = $tagResult['data']['id'];
    }

    // Create multiple activities with various category and tag combinations
    $activityController = new ActivityController($db);
    $activities = [];
    $numActivities = rand(5, 10);
    
    for ($i = 0; $i < $numActivities; $i++) {
        // Randomly assign category
        $category_id = $categories[array_rand($categories)];
        
        // Randomly assign 1-3 tags
        $numActivityTags = rand(1, min(3, count($tags)));
        $activityTags = array_slice($tags, 0, $numActivityTags);
        shuffle($activityTags);
        $activityTags = array_slice($activityTags, 0, $numActivityTags);
        
        $activityData = [
            'title' => 'Activity_' . TestHelpers::randomString(10),
            'description' => 'Description ' . TestHelpers::randomString(20),
            'category_id' => $category_id,
            'tag_ids' => $activityTags
        ];
        
        $createResult = $activityController->create($activityData, $user_id);
        $activities[] = [
            'id' => $createResult['data']['id'],
            'category_id' => $category_id,
            'tag_ids' => $activityTags
        ];
    }

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'categories' => $categories,
        'tags' => $tags,
        'activities' => $activities
    ];
};

// Property: Clearing filters should restore the complete original set
$property = function($data) use ($db) {
    $controller = new ActivityController($db);

    try {
        // Get the original unfiltered set of activities
        $originalResult = $controller->getAll($data['user_id'], []);
        PropertyTestRunner::assertTrue(
            $originalResult['success'],
            "Failed to get original activities"
        );
        
        $originalActivities = $originalResult['data'];
        $originalCount = count($originalActivities);
        
        // Extract activity IDs for comparison
        $originalIds = array_map(function($activity) {
            return $activity['id'];
        }, $originalActivities);
        sort($originalIds);

        // Apply category filter
        $selectedCategory = $data['categories'][array_rand($data['categories'])];
        $filteredResult = $controller->getAll($data['user_id'], ['category_ids' => [$selectedCategory]]);
        PropertyTestRunner::assertTrue(
            $filteredResult['success'],
            "Failed to get filtered activities"
        );
        
        // Clear filters (pass empty filters array)
        $clearedResult = $controller->getAll($data['user_id'], []);
        PropertyTestRunner::assertTrue(
            $clearedResult['success'],
            "Failed to get activities after clearing filters"
        );
        
        $clearedActivities = $clearedResult['data'];
        $clearedCount = count($clearedActivities);
        
        // Extract activity IDs from cleared results
        $clearedIds = array_map(function($activity) {
            return $activity['id'];
        }, $clearedActivities);
        sort($clearedIds);

        // Verify that clearing filters restores the original set
        PropertyTestRunner::assertEquals(
            $originalCount,
            $clearedCount,
            "Cleared filter should return same count as original"
        );
        
        PropertyTestRunner::assertEquals(
            $originalIds,
            $clearedIds,
            "Cleared filter should return same activities as original"
        );

        // Test with tag filter
        $selectedTag = $data['tags'][array_rand($data['tags'])];
        $filteredResult = $controller->getAll($data['user_id'], ['tag_ids' => [$selectedTag]]);
        PropertyTestRunner::assertTrue(
            $filteredResult['success'],
            "Failed to get tag-filtered activities"
        );
        
        // Clear filters again
        $clearedResult2 = $controller->getAll($data['user_id'], []);
        PropertyTestRunner::assertTrue(
            $clearedResult2['success'],
            "Failed to get activities after clearing tag filters"
        );
        
        $clearedIds2 = array_map(function($activity) {
            return $activity['id'];
        }, $clearedResult2['data']);
        sort($clearedIds2);

        PropertyTestRunner::assertEquals(
            $originalIds,
            $clearedIds2,
            "Clearing tag filter should restore original set"
        );

        // Test with combined filters
        $selectedCategory = $data['categories'][array_rand($data['categories'])];
        $selectedTag = $data['tags'][array_rand($data['tags'])];
        $filteredResult = $controller->getAll($data['user_id'], [
            'category_ids' => [$selectedCategory],
            'tag_ids' => [$selectedTag]
        ]);
        PropertyTestRunner::assertTrue(
            $filteredResult['success'],
            "Failed to get combined-filtered activities"
        );
        
        // Clear filters
        $clearedResult3 = $controller->getAll($data['user_id'], []);
        PropertyTestRunner::assertTrue(
            $clearedResult3['success'],
            "Failed to get activities after clearing combined filters"
        );
        
        $clearedIds3 = array_map(function($activity) {
            return $activity['id'];
        }, $clearedResult3['data']);
        sort($clearedIds3);

        PropertyTestRunner::assertEquals(
            $originalIds,
            $clearedIds3,
            "Clearing combined filters should restore original set"
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
    "Property 13: Filter clear restoration",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
