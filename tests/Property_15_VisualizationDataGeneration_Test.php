<?php
/**
 * Feature: omnitrackr, Property 15: Visualization data generation
 * Validates: Requirements 7.1
 * 
 * Property: For any set of activities, accessing the stats page should generate 
 * visualization data structures containing activity metrics.
 */

require_once __DIR__ . '/PropertyTestRunner.php';
require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/CategoryController.php';
require_once __DIR__ . '/../src/controllers/TagController.php';
require_once __DIR__ . '/../src/controllers/ActivityController.php';
require_once __DIR__ . '/../src/controllers/StatsController.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Creates random activities with categories and tags
$generator = function() use ($db) {
    // Create a test user
    $userData = TestHelpers::randomUserData();
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param("sss", $userData['username'], $userData['email'], $hashedPassword);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();

    $categoryController = new CategoryController($db);
    $tagController = new TagController($db);
    $activityController = new ActivityController($db);

    // Generate random number of categories (1-3)
    $num_categories = rand(1, 3);
    $category_ids = [];

    for ($j = 0; $j < $num_categories; $j++) {
        $categoryData = TestHelpers::randomCategoryData();
        $result = $categoryController->create($categoryData, $user_id);
        if ($result['success']) {
            $category_ids[] = $result['data']['id'];
        }
    }

    // Generate random number of tags (1-3)
    $num_tags = rand(1, 3);
    $tag_ids = [];

    for ($j = 0; $j < $num_tags; $j++) {
        $tagData = TestHelpers::randomTagData();
        $result = $tagController->create($tagData, $user_id);
        if ($result['success']) {
            $tag_ids[] = $result['data']['id'];
        }
    }

    // Generate random number of activities (1-10)
    $num_activities = rand(1, 10);

    for ($j = 0; $j < $num_activities; $j++) {
        // Randomly assign category
        $category_id = $category_ids[array_rand($category_ids)];

        // Randomly assign 1-2 tags
        $num_activity_tags = rand(1, min(2, count($tag_ids)));
        $activity_tag_ids = (array)array_rand(array_flip($tag_ids), $num_activity_tags);

        $activityData = TestHelpers::randomActivityData($category_id, $activity_tag_ids);
        $activityController->create($activityData, $user_id);
    }

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'num_activities' => $num_activities
    ];
};

// Property: All stats endpoints should return valid visualization data structures
$property = function($data) use ($db) {
    $statsController = new StatsController($db);

    try {
        // Test overview endpoint
        $overviewResult = $statsController->getOverview($data['user_id']);
        
        PropertyTestRunner::assertTrue(
            $overviewResult['success'],
            "Overview endpoint should return success"
        );

        PropertyTestRunner::assertTrue(
            isset($overviewResult['data']),
            "Overview should contain data field"
        );

        PropertyTestRunner::assertTrue(
            isset($overviewResult['data']['total_activities']),
            "Overview data should contain total_activities"
        );

        PropertyTestRunner::assertTrue(
            isset($overviewResult['data']['total_categories']),
            "Overview data should contain total_categories"
        );

        PropertyTestRunner::assertTrue(
            isset($overviewResult['data']['total_tags']),
            "Overview data should contain total_tags"
        );

        PropertyTestRunner::assertTrue(
            is_int($overviewResult['data']['total_activities']),
            "total_activities should be an integer"
        );

        PropertyTestRunner::assertTrue(
            $overviewResult['data']['total_activities'] >= 0,
            "total_activities should be non-negative"
        );

        // Test category breakdown endpoint
        $categoryResult = $statsController->getCategoryBreakdown($data['user_id']);
        
        PropertyTestRunner::assertTrue(
            $categoryResult['success'],
            "Category breakdown endpoint should return success"
        );

        PropertyTestRunner::assertTrue(
            isset($categoryResult['data']),
            "Category breakdown should contain data field"
        );

        PropertyTestRunner::assertTrue(
            is_array($categoryResult['data']),
            "Category breakdown data should be an array"
        );

        // Verify structure of category breakdown items
        foreach ($categoryResult['data'] as $item) {
            PropertyTestRunner::assertTrue(
                isset($item['category_id']),
                "Category item should have category_id"
            );

            PropertyTestRunner::assertTrue(
                isset($item['category_name']),
                "Category item should have category_name"
            );

            PropertyTestRunner::assertTrue(
                isset($item['category_color']),
                "Category item should have category_color"
            );

            PropertyTestRunner::assertTrue(
                isset($item['activity_count']),
                "Category item should have activity_count"
            );

            PropertyTestRunner::assertTrue(
                is_int($item['activity_count']),
                "activity_count should be an integer"
            );

            PropertyTestRunner::assertTrue(
                $item['activity_count'] >= 0,
                "activity_count should be non-negative"
            );
        }

        // Test tag distribution endpoint
        $tagResult = $statsController->getTagDistribution($data['user_id']);
        
        PropertyTestRunner::assertTrue(
            $tagResult['success'],
            "Tag distribution endpoint should return success"
        );

        PropertyTestRunner::assertTrue(
            isset($tagResult['data']),
            "Tag distribution should contain data field"
        );

        PropertyTestRunner::assertTrue(
            is_array($tagResult['data']),
            "Tag distribution data should be an array"
        );

        // Verify structure of tag distribution items
        foreach ($tagResult['data'] as $item) {
            PropertyTestRunner::assertTrue(
                isset($item['tag_id']),
                "Tag item should have tag_id"
            );

            PropertyTestRunner::assertTrue(
                isset($item['tag_name']),
                "Tag item should have tag_name"
            );

            PropertyTestRunner::assertTrue(
                isset($item['tag_color']),
                "Tag item should have tag_color"
            );

            PropertyTestRunner::assertTrue(
                isset($item['activity_count']),
                "Tag item should have activity_count"
            );

            PropertyTestRunner::assertTrue(
                is_int($item['activity_count']),
                "activity_count should be an integer"
            );

            PropertyTestRunner::assertTrue(
                $item['activity_count'] >= 0,
                "activity_count should be non-negative"
            );
        }

        // Test timeline endpoint
        $timelineResult = $statsController->getTimeline($data['user_id'], 30);
        
        PropertyTestRunner::assertTrue(
            $timelineResult['success'],
            "Timeline endpoint should return success"
        );

        PropertyTestRunner::assertTrue(
            isset($timelineResult['data']),
            "Timeline should contain data field"
        );

        PropertyTestRunner::assertTrue(
            is_array($timelineResult['data']),
            "Timeline data should be an array"
        );

        // Verify structure of timeline items
        foreach ($timelineResult['data'] as $item) {
            PropertyTestRunner::assertTrue(
                isset($item['date']),
                "Timeline item should have date"
            );

            PropertyTestRunner::assertTrue(
                isset($item['count']),
                "Timeline item should have count"
            );

            PropertyTestRunner::assertTrue(
                is_int($item['count']),
                "count should be an integer"
            );

            PropertyTestRunner::assertTrue(
                $item['count'] > 0,
                "count should be positive (only dates with activities are included)"
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
    "Property 15: Visualization data generation",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
