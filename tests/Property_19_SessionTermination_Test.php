<?php
/**
 * Feature: omnitrackr, Property 19: Session termination on logout
 * Validates: Requirements 8.5
 * 
 * Property: For any active user session, logging out should terminate the session 
 * such that subsequent requests require re-authentication.
 */

require_once __DIR__ . '/PropertyTestRunner.php';
require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Clean up any existing test users
TestHelpers::cleanupAllTestUsers($db);

// Create test runner
$runner = new PropertyTestRunner(100, false);

// Generator: Create random user data
$generator = function() {
    return TestHelpers::randomUserData();
};

// Property: Logout should terminate session
$property = function($userData) use ($db) {
    $authController = new AuthController($db);

    try {
        // Register user
        $registerResult = $authController->register($userData);
        PropertyTestRunner::assertTrue(
            $registerResult['success'],
            "Registration should succeed"
        );

        // Login to create session
        $loginResult = $authController->login([
            'username' => $userData['username'],
            'password' => $userData['password']
        ]);

        PropertyTestRunner::assertTrue(
            $loginResult['success'],
            "Login should succeed"
        );

        $session_id = $loginResult['data']['session_id'];
        PropertyTestRunner::assertNotNull($session_id, "Session ID should exist");

        // Verify session exists in database before logout
        $stmt = $db->prepare("SELECT id FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessionBefore = $result->fetch_assoc();
        $stmt->close();

        PropertyTestRunner::assertNotNull(
            $sessionBefore,
            "Session should exist in database before logout"
        );

        // Logout
        $logoutResult = $authController->logout($session_id);

        PropertyTestRunner::assertTrue(
            $logoutResult['success'],
            "Logout should succeed"
        );

        // Verify session no longer exists in database
        $stmt = $db->prepare("SELECT id FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessionAfter = $result->fetch_assoc();
        $stmt->close();

        PropertyTestRunner::assertNull(
            $sessionAfter,
            "Session should not exist in database after logout"
        );

        // Verify that verifying the session now fails
        $verifyResult = $authController->verifySession($session_id);

        PropertyTestRunner::assertTrue(
            !$verifyResult['success'],
            "Session verification should fail after logout"
        );

        PropertyTestRunner::assertEquals(
            'INVALID_SESSION',
            $verifyResult['error']['code'],
            "Error code should be INVALID_SESSION"
        );

        // Verify that logging out again with same session fails
        $logoutResult2 = $authController->logout($session_id);

        PropertyTestRunner::assertTrue(
            !$logoutResult2['success'],
            "Second logout should fail"
        );

        // Clean up
        TestHelpers::cleanupUser($db, $userData['username']);

        return true;
    } catch (Exception $e) {
        // Clean up on failure
        TestHelpers::cleanupUser($db, $userData['username']);
        throw $e;
    }
};

// Run the property test
$result = $runner->runProperty(
    "Property 19: Session termination on logout",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
