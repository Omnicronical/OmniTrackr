<?php
/**
 * Feature: omnitrackr, Property 10: User activity isolation
 * Validates: Requirements 5.1, 8.4
 * 
 * Property: For any user with an active session, all activity operations should 
 * only access and modify data associated with that user's account, never exposing 
 * other users' data.
 */

require_once __DIR__ . '/PropertyTestRunner.php';
require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Clean up any existing test users
TestHelpers::cleanupAllTestUsers($db);

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Create two different users
$generator = function() {
    return [
        'user1' => TestHelpers::randomUserData(),
        'user2' => TestHelpers::randomUserData()
    ];
};

// Property: Users should only access their own data
$property = function($data) use ($db) {
    $authController = new AuthController($db);
    $user1Data = $data['user1'];
    $user2Data = $data['user2'];

    try {
        // Register both users
        $register1 = $authController->register($user1Data);
        PropertyTestRunner::assertTrue($register1['success'], "User 1 registration should succeed");
        
        $register2 = $authController->register($user2Data);
        PropertyTestRunner::assertTrue($register2['success'], "User 2 registration should succeed");

        $user1_id = $register1['data']['user_id'];
        $user2_id = $register2['data']['user_id'];

        // Verify users have different IDs
        PropertyTestRunner::assertTrue(
            $user1_id !== $user2_id,
            "Users should have different IDs"
        );

        // Login both users
        $login1 = $authController->login([
            'username' => $user1Data['username'],
            'password' => $user1Data['password']
        ]);
        PropertyTestRunner::assertTrue($login1['success'], "User 1 login should succeed");

        $login2 = $authController->login([
            'username' => $user2Data['username'],
            'password' => $user2Data['password']
        ]);
        PropertyTestRunner::assertTrue($login2['success'], "User 2 login should succeed");

        $session1_id = $login1['data']['session_id'];
        $session2_id = $login2['data']['session_id'];

        // Verify sessions are different
        PropertyTestRunner::assertTrue(
            $session1_id !== $session2_id,
            "Sessions should be different"
        );

        // Verify session 1 returns user 1 data
        $verify1 = $authController->verifySession($session1_id);
        PropertyTestRunner::assertTrue($verify1['success'], "Session 1 verification should succeed");
        PropertyTestRunner::assertEquals(
            $user1_id,
            $verify1['data']['user_id'],
            "Session 1 should return user 1 ID"
        );
        PropertyTestRunner::assertEquals(
            $user1Data['username'],
            $verify1['data']['username'],
            "Session 1 should return user 1 username"
        );

        // Verify session 2 returns user 2 data
        $verify2 = $authController->verifySession($session2_id);
        PropertyTestRunner::assertTrue($verify2['success'], "Session 2 verification should succeed");
        PropertyTestRunner::assertEquals(
            $user2_id,
            $verify2['data']['user_id'],
            "Session 2 should return user 2 ID"
        );
        PropertyTestRunner::assertEquals(
            $user2Data['username'],
            $verify2['data']['username'],
            "Session 2 should return user 2 username"
        );

        // Verify that session 1 cannot access user 2's data
        // (Session should only return the user it belongs to)
        PropertyTestRunner::assertTrue(
            $verify1['data']['user_id'] !== $user2_id,
            "Session 1 should not return user 2 data"
        );

        // Verify that session 2 cannot access user 1's data
        PropertyTestRunner::assertTrue(
            $verify2['data']['user_id'] !== $user1_id,
            "Session 2 should not return user 1 data"
        );

        // Test with middleware
        // Simulate request with user 1 session
        $_COOKIE['session_id'] = $session1_id;
        $middleware = new AuthMiddleware($db);
        $authUser1 = $middleware->optionalAuth();
        
        PropertyTestRunner::assertNotNull($authUser1, "Middleware should authenticate user 1");
        PropertyTestRunner::assertEquals(
            $user1_id,
            $authUser1['user_id'],
            "Middleware should return user 1 ID"
        );

        // Simulate request with user 2 session
        $_COOKIE['session_id'] = $session2_id;
        $middleware2 = new AuthMiddleware($db);
        $authUser2 = $middleware2->optionalAuth();
        
        PropertyTestRunner::assertNotNull($authUser2, "Middleware should authenticate user 2");
        PropertyTestRunner::assertEquals(
            $user2_id,
            $authUser2['user_id'],
            "Middleware should return user 2 ID"
        );

        // Clean up
        unset($_COOKIE['session_id']);
        TestHelpers::cleanupSession($db, $session1_id);
        TestHelpers::cleanupSession($db, $session2_id);
        TestHelpers::cleanupUser($db, $user1Data['username']);
        TestHelpers::cleanupUser($db, $user2Data['username']);

        return true;
    } catch (Exception $e) {
        // Clean up on failure
        unset($_COOKIE['session_id']);
        TestHelpers::cleanupUser($db, $user1Data['username']);
        TestHelpers::cleanupUser($db, $user2Data['username']);
        throw $e;
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 10: User activity isolation",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
