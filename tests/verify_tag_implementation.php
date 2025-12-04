<?php
/**
 * Verify Tag Implementation
 * 
 * Simple test to verify tag CRUD operations work correctly
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/TagController.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/TestHelpers.php';

echo "=============================================================\n";
echo "Tag Implementation Verification Test\n";
echo "=============================================================\n\n";

// Setup
$database = new Database();
$db = $database->getConnection();
$tagController = new TagController($db);
$authController = new AuthController($db);

$testsPassed = 0;
$testsFailed = 0;

// Create a test user
$userData = TestHelpers::randomUserData();
$registerResult = $authController->register($userData);

if (!$registerResult['success']) {
    echo "✗ Failed to create test user\n";
    exit(1);
}

$userId = $registerResult['data']['user_id'];
echo "✓ Created test user (ID: $userId)\n";

// Test 1: Create a tag
echo "\nTest 1: Create a tag\n";
$tagData = TestHelpers::randomTagData();
$createResult = $tagController->create($tagData, $userId);

if ($createResult['success']) {
    echo "✓ Tag created successfully (ID: {$createResult['data']['id']})\n";
    $tagId = $createResult['data']['id'];
    $testsPassed++;
} else {
    echo "✗ Failed to create tag: {$createResult['error']['message']}\n";
    $testsFailed++;
}

// Test 2: List tags
echo "\nTest 2: List tags\n";
$listResult = $tagController->getAll($userId);

if ($listResult['success'] && count($listResult['data']) === 1) {
    echo "✓ Tag list retrieved successfully (Count: " . count($listResult['data']) . ")\n";
    $testsPassed++;
} else {
    echo "✗ Failed to list tags correctly\n";
    $testsFailed++;
}

// Test 3: Get single tag
echo "\nTest 3: Get single tag\n";
$getResult = $tagController->get($tagId, $userId);

if ($getResult['success'] && $getResult['data']['name'] === $tagData['name']) {
    echo "✓ Tag retrieved successfully\n";
    $testsPassed++;
} else {
    echo "✗ Failed to get tag\n";
    $testsFailed++;
}

// Test 4: Update tag
echo "\nTest 4: Update tag\n";
$newName = TestHelpers::randomTagName();
$updateResult = $tagController->update($tagId, ['name' => $newName], $userId);

if ($updateResult['success'] && $updateResult['data']['name'] === $newName) {
    echo "✓ Tag updated successfully\n";
    $testsPassed++;
} else {
    echo "✗ Failed to update tag\n";
    $testsFailed++;
}

// Test 5: Duplicate name rejection
echo "\nTest 5: Duplicate name rejection\n";
$tag2Data = TestHelpers::randomTagData();
$tagController->create($tag2Data, $userId);
$duplicateResult = $tagController->create($tag2Data, $userId);

if (!$duplicateResult['success'] && $duplicateResult['error']['code'] === 'DUPLICATE_NAME') {
    echo "✓ Duplicate tag name correctly rejected\n";
    $testsPassed++;
} else {
    echo "✗ Failed to reject duplicate tag name\n";
    $testsFailed++;
}

// Test 6: Delete tag
echo "\nTest 6: Delete tag\n";
$deleteResult = $tagController->delete($tagId, $userId);

if ($deleteResult['success']) {
    echo "✓ Tag deleted successfully\n";
    $testsPassed++;
} else {
    echo "✗ Failed to delete tag\n";
    $testsFailed++;
}

// Test 7: Verify tag is deleted
echo "\nTest 7: Verify tag is deleted\n";
$getDeletedResult = $tagController->get($tagId, $userId);

if (!$getDeletedResult['success'] && $getDeletedResult['error']['code'] === 'NOT_FOUND') {
    echo "✓ Deleted tag is no longer accessible\n";
    $testsPassed++;
} else {
    echo "✗ Deleted tag is still accessible\n";
    $testsFailed++;
}

// Cleanup
TestHelpers::cleanupUser($db, $userData['username']);
echo "\n✓ Cleaned up test data\n";

// Summary
echo "\n=============================================================\n";
echo "Test Summary\n";
echo "=============================================================\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";
echo "=============================================================\n\n";

if ($testsFailed > 0) {
    echo "✗ Some tests failed\n\n";
    exit(1);
} else {
    echo "✓ All tests passed!\n\n";
    exit(0);
}
