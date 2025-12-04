<?php
/**
 * Authentication Middleware
 * 
 * Protects routes by verifying user authentication
 */

require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
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
     * Authenticate request
     * 
     * @return array|null User data if authenticated, null otherwise
     */
    public function authenticate() {
        // Get session ID from cookie or header
        $session_id = $this->getSessionId();

        if (!$session_id) {
            $this->sendUnauthorizedResponse('No session provided');
            return null;
        }

        $session = new Session($this->db);

        // Verify session exists and is valid
        if (!$session->findById($session_id)) {
            $this->sendUnauthorizedResponse('Invalid or expired session');
            return null;
        }

        // Get user data
        $user = new User($this->db);
        if (!$user->findById($session->user_id)) {
            $this->sendUnauthorizedResponse('User not found');
            return null;
        }

        // Return user data
        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email
        ];
    }

    /**
     * Get session ID from request
     * 
     * @return string|null Session ID or null if not found
     */
    private function getSessionId() {
        // Check Authorization header first
        // Use function_exists to handle CLI vs web server contexts
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for CLI/testing environments
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }

        // Check cookie
        if (isset($_COOKIE['session_id'])) {
            return $_COOKIE['session_id'];
        }

        // Check POST/GET parameter (fallback)
        if (isset($_POST['session_id'])) {
            return $_POST['session_id'];
        }

        if (isset($_GET['session_id'])) {
            return $_GET['session_id'];
        }

        return null;
    }

    /**
     * Send unauthorized response
     * 
     * @param string $message Error message
     */
    private function sendUnauthorizedResponse($message) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
                'details' => []
            ]
        ]);
        exit;
    }

    /**
     * Require authentication (middleware function)
     * 
     * @return array User data if authenticated, exits with 401 otherwise
     */
    public function requireAuth() {
        $user = $this->authenticate();
        if ($user === null) {
            exit; // Response already sent by authenticate()
        }
        return $user;
    }

    /**
     * Optional authentication (doesn't exit if not authenticated)
     * 
     * @return array|null User data if authenticated, null otherwise
     */
    public function optionalAuth() {
        $session_id = $this->getSessionId();

        if (!$session_id) {
            return null;
        }

        $session = new Session($this->db);

        if (!$session->findById($session_id)) {
            return null;
        }

        $user = new User($this->db);
        if (!$user->findById($session->user_id)) {
            return null;
        }

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email
        ];
    }
}
