<?php
/**
 * OmniTrackr Application Entry Point
 * 
 * This file serves as the main entry point for the application.
 * It handles routing and initializes the application.
 */

// Set error reporting based on environment
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session
session_start();

// Load database configuration
require_once __DIR__ . '/../src/config/database.php';

// Get request URI and method
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$uri = parse_url($request_uri, PHP_URL_PATH);
$uri = trim($uri, '/');

// Check if this is an API request
$isApiRequest = strpos($uri, 'api/') === 0;

if ($isApiRequest) {
    // Set JSON headers for API requests
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Basic routing (will be expanded in later tasks)
if ($uri === '' || $uri === 'index.php') {
    // Serve the frontend application
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmniTrackr - Activity Tracking</title>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav id="main-nav" class="hidden">
            <div class="nav-container">
                <h1 class="nav-logo">OmniTrackr</h1>
                <div class="nav-actions">
                    <span id="user-display" class="user-display"></span>
                    <button id="logout-btn" class="btn btn-secondary">Logout</button>
                </div>
            </div>
        </nav>

        <!-- Authentication Container -->
        <div id="auth-container" class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">OmniTrackr</h1>
                <p class="auth-subtitle">Track your activities with ease</p>

                <!-- Login Form -->
                <form id="login-form" class="auth-form">
                    <h2>Login</h2>
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input 
                            type="text" 
                            id="login-username" 
                            name="username" 
                            required 
                            autocomplete="username"
                            class="form-input"
                        >
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input 
                            type="password" 
                            id="login-password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            class="form-input"
                        >
                    </div>
                    <div id="login-error" class="error-message hidden"></div>
                    <button type="submit" class="btn btn-primary">Login</button>
                    <p class="auth-switch">
                        Don&apos;t have an account? 
                        <a href="#" id="show-register">Register here</a>
                    </p>
                </form>

                <!-- Registration Form -->
                <form id="register-form" class="auth-form hidden">
                    <h2>Register</h2>
                    <div class="form-group">
                        <label for="register-username">Username</label>
                        <input 
                            type="text" 
                            id="register-username" 
                            name="username" 
                            required 
                            autocomplete="username"
                            class="form-input"
                            minlength="3"
                        >
                        <small class="form-hint">At least 3 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input 
                            type="email" 
                            id="register-email" 
                            name="email" 
                            required 
                            autocomplete="email"
                            class="form-input"
                        >
                    </div>
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input 
                            type="password" 
                            id="register-password" 
                            name="password" 
                            required 
                            autocomplete="new-password"
                            class="form-input"
                            minlength="6"
                        >
                        <small class="form-hint">At least 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="register-password-confirm">Confirm Password</label>
                        <input 
                            type="password" 
                            id="register-password-confirm" 
                            name="password_confirm" 
                            required 
                            autocomplete="new-password"
                            class="form-input"
                        >
                    </div>
                    <div id="register-error" class="error-message hidden"></div>
                    <button type="submit" class="btn btn-primary">Register</button>
                    <p class="auth-switch">
                        Already have an account? 
                        <a href="#" id="show-login">Login here</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Main Application Container (hidden until authenticated) -->
        <div id="main-container" class="main-container hidden">
            <!-- View Tabs -->
            <div class="view-tabs">
                <button id="tab-activities" class="tab-button active" data-view="activities">
                    📋 Activities
                </button>
                <button id="tab-manage" class="tab-button" data-view="manage">
                    ⚙️ Manage
                </button>
            </div>

            <!-- Activities View -->
            <div id="view-activities" class="view-content active">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h2 class="dashboard-title">My Activities</h2>
                    <button id="add-activity-btn" class="btn btn-primary btn-add">
                        <span class="btn-icon">+</span> Add Activity
                    </button>
                </div>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <div class="filter-section">
                    <h3 class="filter-title">Filters</h3>
                    <button id="clear-filters-btn" class="btn-link">Clear All</button>
                </div>
                <div class="filter-group">
                    <h4 class="filter-group-title">Categories</h4>
                    <div id="category-filters" class="filter-checkboxes">
                        <!-- Category filters will be populated dynamically -->
                    </div>
                </div>
                <div class="filter-group">
                    <h4 class="filter-group-title">Tags</h4>
                    <div id="tag-filters" class="filter-checkboxes">
                        <!-- Tag filters will be populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Activities Grid -->
            <div id="activities-container" class="activities-container">
                <div id="activities-grid" class="activities-grid">
                    <!-- Activity cards will be populated dynamically -->
                </div>
                <div id="empty-state" class="empty-state hidden">
                    <div class="empty-state-icon">📋</div>
                    <h3>No activities yet</h3>
                    <p>Create your first activity to get started tracking your work!</p>
                    <button class="btn btn-primary" onclick="document.getElementById(&apos;add-activity-btn&apos;).click()">
                        Add Your First Activity
                    </button>
                </div>
                <div id="loading-state" class="loading-state hidden">
                    <div class="spinner"></div>
                    <p>Loading activities...</p>
                </div>
            </div>

            <!-- Activity Form Modal -->
            <div id="activity-modal" class="modal hidden">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modal-title">Add Activity</h3>
                        <button class="modal-close" id="close-modal-btn">&times;</button>
                    </div>
                    <form id="activity-form" class="activity-form">
                        <input type="hidden" id="activity-id" name="id">
                        
                        <div class="form-group">
                            <label for="activity-title">Title *</label>
                            <input 
                                type="text" 
                                id="activity-title" 
                                name="title" 
                                class="form-input" 
                                required
                                placeholder="Enter activity title"
                            >
                        </div>

                        <div class="form-group">
                            <label for="activity-description">Description</label>
                            <textarea 
                                id="activity-description" 
                                name="description" 
                                class="form-input form-textarea"
                                rows="4"
                                placeholder="Enter activity description (optional)"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label for="activity-category">Category</label>
                            <select id="activity-category" name="category_id" class="form-input">
                                <option value="">Select a category (optional)</option>
                                <!-- Categories will be populated dynamically -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tags</label>
                            <div id="activity-tags" class="tag-selector">
                                <!-- Tags will be populated dynamically -->
                            </div>
                        </div>

                        <div id="activity-form-error" class="error-message hidden"></div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" id="cancel-activity-btn">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="save-activity-btn">Save Activity</button>
                        </div>
                    </form>
                </div>
            </div>
            </div>

            <!-- Management View -->
            <div id="view-manage" class="view-content">
                <div class="management-container">
                    <!-- Categories Management -->
                    <div class="management-section">
                        <div class="management-header">
                            <h2 class="management-title">Categories</h2>
                            <button id="add-category-btn" class="btn btn-primary btn-add">
                                <span class="btn-icon">+</span> Add Category
                            </button>
                        </div>
                        <div id="categories-list" class="management-list">
                            <!-- Categories will be populated dynamically -->
                        </div>
                        <div id="categories-empty" class="management-empty hidden">
                            <p>No categories yet. Create your first category!</p>
                        </div>
                    </div>

                    <!-- Tags Management -->
                    <div class="management-section">
                        <div class="management-header">
                            <h2 class="management-title">Tags</h2>
                            <button id="add-tag-btn" class="btn btn-primary btn-add">
                                <span class="btn-icon">+</span> Add Tag
                            </button>
                        </div>
                        <div id="tags-list" class="management-list">
                            <!-- Tags will be populated dynamically -->
                        </div>
                        <div id="tags-empty" class="management-empty hidden">
                            <p>No tags yet. Create your first tag!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/app.js"></script>
</body>
</html>';
    exit();
}

// API routing (will be implemented in later tasks)
if (strpos($uri, 'api/') === 0) {
    // Test database connection
    if ($uri === 'api/health') {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $db->closeConnection();
            
            echo json_encode([
                'success' => true,
                'message' => 'Database connection successful',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'DB_CONNECTION_ERROR',
                    'message' => 'Database connection failed',
                    'details' => $e->getMessage()
                ]
            ]);
        }
        exit();
    }
    
    // Other API routes will be added in subsequent tasks
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'API endpoint not found',
            'details' => []
        ]
    ]);
    exit();
}

// 404 for other routes
http_response_code(404);
echo json_encode([
    'success' => false,
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'Route not found',
        'details' => []
    ]
]);
