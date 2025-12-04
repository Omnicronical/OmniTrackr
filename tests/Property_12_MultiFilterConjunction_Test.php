<?php
/**
 * Feature: omnitrackr, Property 12: Multi-filter conjunction
 * Validates: Requirements 6.1, 6.2, 6.3
 * 
 * Property: For any set of activities and any combination of category and tag filters,
 * applying the filters should return only activities that match all selected criteria.
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

// Property: Filtered results should match all selected criteria
$property = function($data) use ($db) {
    $controller = new ActivityController($db);

    try {
        // Test 1: Filter by single category
        $selectedCategory = $data['categories'][array_rand($data['categories'])];
        $filters = ['category_ids' => [$selectedCategory]];
        
        $result = $controller->getAll($data['user_id'], $filters);
        PropertyTestRunner::assertTrue($result['success'], "Failed to get filtered activities");
        
        foreach ($result['data'] as $activity) {
            PropertyTestRunner::assertEquals(
                $selectedCategory,
                $activity['category_id'],
                "Activity should have the filtered category"
            );
        }

        // Test 2: Filter by multiple categories
        $numCatsToFilter = min(2, count($data['categories']));
        $selectedCategories = array_slice($data['categories'], 0, $numCatsToFilter);
        $filters = ['category_ids' => $selectedCategories];
        
        $result = $controller->getAll($data['user_id'], $filters);
        PropertyTestRunner::assertTrue($result['success'], "Failed to get filtered activities");
        
        foreach ($result['data'] as $activity) {
            PropertyTestRunner::assertTrue(
                in_array($activity['category_id'], $selectedCategories),
                "Activity should have one of the filtered categories"
            );
        }

        // Test 3: Filter by single tag
        $selectedTag = $data['tags'][array_rand($data['tags'])];
        $filters = ['tag_ids' => [$selectedTag]];
        
        $result = $controller->getAll($data['user_id'], $filters);
        PropertyTestRunner::assertTrue($result['success'], "Failed to get filtered activities");
        
        foreach ($result['data'] as $activity) {
            PropertyTestRunner::assertTrue(
                in_array($selectedTag, $activity['tag_ids']),
                "Activity should have the filtered tag"
            );
        }

        // Test 4: Filter by multiple tags (AND logic - must have ALL tags)
        $numTagsToFilter = min(2, count($data['tags']));
        $selectedTags = array_slice($data['tags'], 0, $numTagsToFilter);
        $filters = ['tag_ids' => $selectedTags];
        
        $result = $controller->getAll($data['user_id'], $filters);
        PropertyTestRunner::assertTrue($result['success'], "Failed to get filtered activities");
        
        foreach ($result['data'] as $activity) {
            foreach ($selectedTags as $tag) {
                PropertyTestRunner::assertTrue(
                    in_array($tag, $activity['tag_ids']),
                    "Activity should have ALL filtered tags (conjunction)"
                );
            }
        }

        // Test 5: Filter by both category and tags (conjunction)
        $selectedCategory = $data['categories'][array_rand($data['categories'])];
        $selectedTag = $data['tags'][array_rand($data['tags'])];
        $filters = [
            'category_ids' => [$selectedCategory],
            'tag_ids' => [$selectedTag]
        ];
        
        $result = $controller->getAll($data['user_id'], $filters);
        PropertyTestRunner::assertTrue($result['success'], "Failed to get filtered activities");
        
        foreach ($result['data'] as $activity) {
            PropertyTestRunner::assertEquals(
                $selectedCategory,
                $activity['category_id'],
                "Activity should have the filtered category"
            );
            PropertyTestRunner::assertTrue(
                in_array($selectedTag, $activity['tag_ids']),
                "Activity should have the filtered tag"
            );
        }

        // Test 6: Verify that unfiltered results contain all activities
        $unfilteredResult = $controller->getAll($data['user_id'], []);
        PropertyTestRunner::assertTrue($unfilteredResult['success'], "Failed to get all activities");
        PropertyTestRunner::assertEquals(
            count($data['activities']),
            count($unfilteredResult['data']),
            "Unfiltered results should contain all activities"
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
    "Property 12: Multi-filter conjunction",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
