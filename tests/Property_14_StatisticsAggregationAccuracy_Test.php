<?php
/**
 * Feature: omnitrackr, Property 14: Statistics aggregation accuracy
 * Validates: Requirements 7.2, 7.4
 * 
 * Property: For any set of activities with categories and tags, calculating statistics 
 * should produce accurate counts and distributions grouped by category and tag.
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

    // Generate random number of categories (0-5)
    $num_categories = rand(0, 5);
    $category_ids = [];
    $expected_category_counts = [];

    for ($j = 0; $j < $num_categories; $j++) {
        $categoryData = TestHelpers::randomCategoryData();
        $result = $categoryController->create($categoryData, $user_id);
        if ($result['success']) {
            $cat_id = $result['data']['id'];
            $category_ids[] = $cat_id;
            $expected_category_counts[$cat_id] = 0;
        }
    }

    // Generate random number of tags (0-5)
    $num_tags = rand(0, 5);
    $tag_ids = [];
    $expected_tag_counts = [];

    for ($j = 0; $j < $num_tags; $j++) {
        $tagData = TestHelpers::randomTagData();
        $result = $tagController->create($tagData, $user_id);
        if ($result['success']) {
            $tag_id = $result['data']['id'];
            $tag_ids[] = $tag_id;
            $expected_tag_counts[$tag_id] = 0;
        }
    }

    // Generate random number of activities (1-10)
    $num_activities = rand(1, 10);
    $uncategorized_count = 0;

    for ($j = 0; $j < $num_activities; $j++) {
        // Randomly assign category (or null)
        $category_id = null;
        if (!empty($category_ids) && rand(0, 1) === 1) {
            $category_id = $category_ids[array_rand($category_ids)];
            $expected_category_counts[$category_id]++;
        } else {
            $uncategorized_count++;
        }

        // Randomly assign tags (0-3 tags)
        $activity_tag_ids = [];
        if (!empty($tag_ids)) {
            $num_activity_tags = rand(0, min(3, count($tag_ids)));
            if ($num_activity_tags > 0) {
                $selected_tags = (array)array_rand(array_flip($tag_ids), $num_activity_tags);
                foreach ($selected_tags as $tag_id) {
                    $activity_tag_ids[] = $tag_id;
                    $expected_tag_counts[$tag_id]++;
                }
            }
        }

        $activityData = TestHelpers::randomActivityData($category_id, $activity_tag_ids);
        $activityController->create($activityData, $user_id);
    }

    return [
        'user_id' => $user_id,
        'username' => $userData['username'],
        'num_activities' => $num_activities,
        'num_categories' => $num_categories,
        'num_tags' => $num_tags,
        'expected_category_counts' => $expected_category_counts,
        'expected_tag_counts' => $expected_tag_counts,
        'uncategorized_count' => $uncategorized_count
    ];
};

// Property: Statistics should accurately reflect the created data
$property = function($data) use ($db) {
    $statsController = new StatsController($db);

    try {
        // Get statistics
        $overviewResult = $statsController->getOverview($data['user_id']);
        $categoryResult = $statsController->getCategoryBreakdown($data['user_id']);
        $tagResult = $statsController->getTagDistribution($data['user_id']);

        // Verify overview statistics
        PropertyTestRunner::assertTrue(
            $overviewResult['success'],
            "Failed to get overview statistics"
        );

        $overview = $overviewResult['data'];
        PropertyTestRunner::assertEquals(
            $data['num_activities'],
            $overview['total_activities'],
            "Overview total_activities mismatch"
        );

        PropertyTestRunner::assertEquals(
            $data['num_categories'],
            $overview['total_categories'],
            "Overview total_categories mismatch"
        );

        PropertyTestRunner::assertEquals(
            $data['num_tags'],
            $overview['total_tags'],
            "Overview total_tags mismatch"
        );

        // Verify category breakdown
        PropertyTestRunner::assertTrue(
            $categoryResult['success'],
            "Failed to get category breakdown"
        );

        $categoryBreakdown = $categoryResult['data'];
        foreach ($categoryBreakdown as $item) {
            if ($item['category_id'] === null) {
                // Uncategorized activities
                PropertyTestRunner::assertEquals(
                    $data['uncategorized_count'],
                    $item['activity_count'],
                    "Uncategorized count mismatch"
                );
            } else {
                $cat_id = $item['category_id'];
                PropertyTestRunner::assertTrue(
                    isset($data['expected_category_counts'][$cat_id]),
                    "Unexpected category ID in breakdown: {$cat_id}"
                );
                PropertyTestRunner::assertEquals(
                    $data['expected_category_counts'][$cat_id],
                    $item['activity_count'],
                    "Category {$cat_id} count mismatch"
                );
            }
        }

        // Verify tag distribution
        PropertyTestRunner::assertTrue(
            $tagResult['success'],
            "Failed to get tag distribution"
        );

        $tagDistribution = $tagResult['data'];
        foreach ($tagDistribution as $item) {
            $tag_id = $item['tag_id'];
            PropertyTestRunner::assertTrue(
                isset($data['expected_tag_counts'][$tag_id]),
                "Unexpected tag ID in distribution: {$tag_id}"
            );
            PropertyTestRunner::assertEquals(
                $data['expected_tag_counts'][$tag_id],
                $item['activity_count'],
                "Tag {$tag_id} count mismatch"
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
    "Property 14: Statistics aggregation accuracy",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
