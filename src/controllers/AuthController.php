<?php
/**
 * Authentication Controller
 * 
 * Handles user registration, login, and logout operations
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Session.php';

class AuthController {
    private $db;

    public function __construct($db = null) {
        if ($db === null) {
            $database = new Database();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }
    }

    /**
     * Register a new user
     * 
     * @param array $data User registration data
     * @return array Response with success status and data/error
     */
    public function register($data) {
        try {
            // Validate required fields
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Username, email, and password are required',
                        'details' => []
                    ]
                ];
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Invalid email format',
                        'details' => []
                    ]
                ];
            }

            // Validate password length
            if (strlen($data['password']) < 6) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Password must be at least 6 characters long',
                        'details' => []
                    ]
                ];
            }

            $user = new User($this->db);

            // Check if username already exists
            if ($user->usernameExists($data['username'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_USERNAME',
                        'message' => 'Username already exists',
                        'details' => []
                    ]
                ];
            }

            // Check if email already exists
            if ($user->emailExists($data['email'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_EMAIL',
                        'message' => 'Email already exists',
                        'details' => []
                    ]
                ];
            }

            // Create user
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->password_hash = $data['password']; // Will be hashed in User model

            $user_id = $user->create();

            if ($user_id) {
                return [
                    'success' => true,
                    'data' => [
                        'user_id' => $user_id,
                        'username' => $user->username,
                        'email' => $user->email
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to create user',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Login user
     * 
     * @param array $data Login credentials
     * @return array Response with success status and session data/error
     */
    public function login($data) {
        try {
            // Validate required fields
            if (empty($data['username']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Username and password are required',
                        'details' => []
                    ]
                ];
            }

            $user = new User($this->db);

            // Find user by username
            if (!$user->findByUsername($data['username'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS',
                        'message' => 'Invalid username or password',
                        'details' => []
                    ]
                ];
            }

            // Verify password
            if (!$user->verifyPassword($data['password'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS',
                        'message' => 'Invalid username or password',
                        'details' => []
                    ]
                ];
            }

            // Create session
            $session = new Session($this->db);
            $session->user_id = $user->id;
            
            // Set session expiration (24 hours from now)
            $session_lifetime = getenv('SESSION_LIFETIME') ?: 86400;
            $session->expires_at = date('Y-m-d H:i:s', time() + $session_lifetime);

            $session_id = $session->create();

            if ($session_id) {
                return [
                    'success' => true,
                    'data' => [
                        'session_id' => $session_id,
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'expires_at' => $session->expires_at
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Failed to create session',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Logout user
     * 
     * @param string $session_id Session ID to terminate
     * @return array Response with success status
     */
    public function logout($session_id) {
        try {
            if (empty($session_id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Session ID is required',
                        'details' => []
                    ]
                ];
            }

            $session = new Session($this->db);

            // Find and delete session
            if ($session->findById($session_id)) {
                if ($session->delete()) {
                    return [
                        'success' => true,
                        'data' => [
                            'message' => 'Logged out successfully'
                        ]
                    ];
                }
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_SESSION',
                    'message' => 'Invalid or expired session',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }

    /**
     * Verify session and get user
     * 
     * @param string $session_id Session ID to verify
     * @return array Response with user data or error
     */
    public function verifySession($session_id) {
        try {
            if (empty($session_id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Session ID is required',
                        'details' => []
                    ]
                ];
            }

            $session = new Session($this->db);

            if (!$session->findById($session_id)) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_SESSION',
                        'message' => 'Invalid or expired session',
                        'details' => []
                    ]
                ];
            }

            $user = new User($this->db);
            if ($user->findById($session->user_id)) {
                return [
                    'success' => true,
                    'data' => [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'User not found',
                    'details' => []
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Database operation failed',
                    'details' => []
                ]
            ];
        }
    }
}
