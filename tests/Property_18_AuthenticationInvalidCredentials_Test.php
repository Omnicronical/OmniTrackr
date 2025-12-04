<?php
/**
 * Feature: omnitrackr, Property 18: Authentication failure with invalid credentials
 * Validates: Requirements 8.3
 * 
 * Property: For any registered user, attempting to log in with incorrect credentials 
 * should reject authentication and return an error without establishing a session.
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

// Generator: Create random user data and wrong password
$generator = function() {
    $userData = TestHelpers::randomUserData();
    $wrongPassword = TestHelpers::randomPassword();
    
    // Ensure wrong password is different from correct password
    while ($wrongPassword === $userData['password']) {
        $wrongPassword = TestHelpers::randomPassword();
    }
    
    return [
        'user' => $userData,
        'wrong_password' => $wrongPassword
    ];
};

// Property: Invalid credentials should fail authentication
$property = function($data) use ($db) {
    $authController = new AuthController($db);
    $userData = $data['user'];
    $wrongPassword = $data['wrong_password'];

    try {
        // First, register the user
        $registerResult = $authController->register($userData);
        PropertyTestRunner::assertTrue(
            $registerResult['success'],
            "Registration should succeed"
        );

        // Try to login with wrong password
        $loginResult = $authController->login([
            'username' => $userData['username'],
            'password' => $wrongPassword
        ]);

        // Verify login failed
        PropertyTestRunner::assertTrue(
            !$loginResult['success'],
            "Login should fail with invalid credentials"
        );

        // Verify error code is correct
        PropertyTestRunner::assertEquals(
            'INVALID_CREDENTIALS',
            $loginResult['error']['code'],
            "Error code should be INVALID_CREDENTIALS"
        );

        // Verify no session was created
        PropertyTestRunner::assertTrue(
            !isset($loginResult['data']['session_id']),
            "No session ID should be returned"
        );

        // Verify no sessions exist for this user in database
        $user = TestHelpers::getUserByUsername($db, $userData['username']);
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        PropertyTestRunner::assertEquals(
            0,
            (int)$row['count'],
            "No sessions should exist for user after failed login"
        );

        // Also test with non-existent username
        $loginResult2 = $authController->login([
            'username' => 'nonexistent_' . TestHelpers::randomString(),
            'password' => $wrongPassword
        ]);

        PropertyTestRunner::assertTrue(
            !$loginResult2['success'],
            "Login should fail with non-existent username"
        );

        PropertyTestRunner::assertEquals(
            'INVALID_CREDENTIALS',
            $loginResult2['error']['code'],
            "Error code should be INVALID_CREDENTIALS for non-existent user"
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
    "Property 18: Authentication failure with invalid credentials",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
