<?php
/**
 * Feature: omnitrackr, Property 16: User registration with encryption
 * Validates: Requirements 8.1
 * 
 * Property: For any valid credentials, registering a new user should create 
 * an account with the password stored in encrypted form, never as plaintext.
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

// Property: Password must be encrypted (bcrypt) in database
$property = function($userData) use ($db) {
    // Register user
    $authController = new AuthController($db);
    $result = $authController->register($userData);

    try {
        // Verify registration succeeded
        PropertyTestRunner::assertTrue(
            $result['success'],
            "Registration should succeed"
        );

        // Get user from database
        $user = TestHelpers::getUserByUsername($db, $userData['username']);
        PropertyTestRunner::assertNotNull($user, "User should exist in database");

        // Verify password is NOT stored as plaintext
        PropertyTestRunner::assertTrue(
            $user['password_hash'] !== $userData['password'],
            "Password should not be stored as plaintext"
        );

        // Verify password is stored as bcrypt hash
        PropertyTestRunner::assertTrue(
            TestHelpers::isBcryptHash($user['password_hash']),
            "Password should be stored as bcrypt hash"
        );

        // Verify the hash can verify the original password
        PropertyTestRunner::assertTrue(
            password_verify($userData['password'], $user['password_hash']),
            "Stored hash should verify the original password"
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
    "Property 16: User registration with encryption",
    $generator,
    $property
);

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
