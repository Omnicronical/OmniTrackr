<?php
/**
 * Feature: omnitrackr, Property 17: Authentication success with valid credentials
 * Validates: Requirements 8.2
 * 
 * Property: For any registered user, logging in with correct credentials should 
 * authenticate the user and establish a valid session.
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

// Property: Valid credentials should authenticate and create session
$property = function($userData) use ($db) {
    $authController = new AuthController($db);

    try {
        // First, register the user
        $registerResult = $authController->register($userData);
        PropertyTestRunner::assertTrue(
            $registerResult['success'],
            "Registration should succeed"
        );

        // Now login with the same credentials
        $loginResult = $authController->login([
            'username' => $userData['username'],
            'password' => $userData['password']
        ]);

        // Verify login succeeded
        PropertyTestRunner::assertTrue(
            $loginResult['success'],
            "Login should succeed with valid credentials"
        );

        // Verify session was created
        PropertyTestRunner::assertNotNull(
            $loginResult['data']['session_id'] ?? null,
            "Session ID should be returned"
        );

        // Verify user data is returned
        PropertyTestRunner::assertEquals(
            $userData['username'],
            $loginResult['data']['username'],
            "Username should match"
        );

        PropertyTestRunner::assertEquals(
            $userData['email'],
            $loginResult['data']['email'],
            "Email should match"
        );

        // Verify session exists in database and is valid
        $session_id = $loginResult['data']['session_id'];
        $stmt = $db->prepare("SELECT id, user_id, expires_at FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        PropertyTestRunner::assertNotNull($session, "Session should exist in database");
        PropertyTestRunner::assertTrue(
            strtotime($session['expires_at']) > time(),
            "Session should not be expired"
        );

        // Clean up
        TestHelpers::cleanupSession($db, $session_id);
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
    "Property 17: Authentication success with valid credentials",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
