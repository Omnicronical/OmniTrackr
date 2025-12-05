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
    <meta name="description" content="Track your activities, projects, and tasks with OmniTrackr">
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <div id="app">
        <!-- Skip to main content link for keyboard users -->
        <a href="#main-content" class="skip-link">Skip to main content</a>
        
        <!-- Navigation -->
        <nav id="main-nav" class="hidden" role="navigation" aria-label="Main navigation">
            <div class="nav-container">
                <h1 class="nav-logo">OmniTrackr</h1>
                <div class="nav-actions">
                    <span id="user-display" class="user-display" aria-live="polite"></span>
                    <button id="logout-btn" class="btn btn-secondary" aria-label="Logout from your account">Logout</button>
                </div>
            </div>
        </nav>

        <!-- Authentication Container -->
        <div id="auth-container" class="auth-container" role="main" aria-label="Authentication">
            <div class="auth-card">
                <h1 class="auth-title">OmniTrackr</h1>
                <p class="auth-subtitle">Track your activities with ease</p>

                <!-- Login Form -->
                <form id="login-form" class="auth-form" aria-labelledby="login-heading">
                    <h2 id="login-heading">Login</h2>
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
                    <div id="login-error" class="error-message hidden" role="alert" aria-live="assertive"></div>
                    <button type="submit" class="btn btn-primary" aria-label="Login to your account">Login</button>
                    <p class="auth-switch">
                        Don&apos;t have an account? 
                        <a href="#" id="show-register">Register here</a>
                    </p>
                </form>

                <!-- Registration Form -->
                <form id="register-form" class="auth-form hidden" aria-labelledby="register-heading">
                    <h2 id="register-heading">Register</h2>
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
                    <div id="register-error" class="error-message hidden" role="alert" aria-live="assertive"></div>
                    <button type="submit" class="btn btn-primary" aria-label="Create new account">Register</button>
                    <p class="auth-switch">
                        Already have an account? 
                        <a href="#" id="show-login">Login here</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Main Application Container (hidden until authenticated) -->
        <div id="main-container" class="main-container hidden">
            <main id="main-content" role="main">
            <!-- View Tabs -->
            <div class="view-tabs" role="tablist" aria-label="View selection">
                <button id="tab-activities" class="tab-button active" data-view="activities" role="tab" aria-selected="true" aria-controls="view-activities">
                    📋 Activities
                </button>
                <button id="tab-stats" class="tab-button" data-view="stats" role="tab" aria-selected="false" aria-controls="view-stats">
                    📊 Statistics
                </button>
                <button id="tab-manage" class="tab-button" data-view="manage" role="tab" aria-selected="false" aria-controls="view-manage">
                    ⚙️ Manage
                </button>
            </div>

            <!-- Activities View -->
            <div id="view-activities" class="view-content active" role="tabpanel" aria-labelledby="tab-activities" aria-hidden="false">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h2 class="dashboard-title">My Activities</h2>
                    <button id="add-activity-btn" class="btn btn-primary btn-add" aria-label="Add new activity">
                        <span class="btn-icon" aria-hidden="true">+</span> Add Activity
                    </button>
                </div>

            <!-- Filter Panel -->
            <div class="filter-panel" role="region" aria-label="Activity filters">
                <div class="filter-section">
                    <h3 class="filter-title">Filters <span id="filter-count" class="filter-count hidden" aria-label="Active filters count"></span></h3>
                    <button id="clear-filters-btn" class="btn-link" aria-label="Clear all filters">Clear All</button>
                </div>
                <div class="filter-group">
                    <h4 class="filter-group-title">Categories</h4>
                    <div id="category-filters" class="filter-checkboxes" role="group" aria-label="Category filters">
                        <!-- Category filters will be populated dynamically -->
                    </div>
                </div>
                <div class="filter-group">
                    <h4 class="filter-group-title">Tags</h4>
                    <div id="tag-filters" class="filter-checkboxes" role="group" aria-label="Tag filters">
                        <!-- Tag filters will be populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Activities Grid -->
            <div id="activities-container" class="activities-container">
                <div id="activities-grid" class="activities-grid" role="list" aria-label="Activities list">
                    <!-- Activity cards will be populated dynamically -->
                </div>
                <div id="empty-state" class="empty-state hidden" role="status">
                    <div class="empty-state-icon" aria-hidden="true">📋</div>
                    <h3>No activities yet</h3>
                    <p>Create your first activity to get started tracking your work!</p>
                    <button class="btn btn-primary" onclick="document.getElementById(&apos;add-activity-btn&apos;).click()" aria-label="Add your first activity">
                        Add Your First Activity
                    </button>
                </div>
                <div id="loading-state" class="loading-state hidden" role="status" aria-live="polite">
                    <div class="spinner" aria-hidden="true"></div>
                    <p>Loading activities...</p>
                </div>
            </div>

            <!-- Activity Form Modal -->
            <div id="activity-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true">
                <div class="modal-overlay" aria-hidden="true"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modal-title">Add Activity</h3>
                        <button class="modal-close" id="close-modal-btn" aria-label="Close dialog">&times;</button>
                    </div>
                    <form id="activity-form" class="activity-form" aria-labelledby="modal-title">
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
                            <div id="activity-tags" class="tag-selector" role="group" aria-label="Select tags for activity">
                                <!-- Tags will be populated dynamically -->
                            </div>
                        </div>

                        <div id="activity-form-error" class="error-message hidden" role="alert" aria-live="assertive"></div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" id="cancel-activity-btn" aria-label="Cancel and close dialog">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="save-activity-btn" aria-label="Save activity">Save Activity</button>
                        </div>
                    </form>
                </div>
            </div>
            </div>

            <!-- Statistics View -->
            <div id="view-stats" class="view-content" role="tabpanel" aria-labelledby="tab-stats" aria-hidden="true">
                <div class="stats-container">
                    <!-- Overview Cards -->
                    <div class="stats-overview">
                        <div class="stat-card">
                            <div class="stat-icon">📋</div>
                            <div class="stat-content">
                                <div class="stat-value" id="stat-total-activities">0</div>
                                <div class="stat-label">Total Activities</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📁</div>
                            <div class="stat-content">
                                <div class="stat-value" id="stat-total-categories">0</div>
                                <div class="stat-label">Categories</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🏷️</div>
                            <div class="stat-content">
                                <div class="stat-value" id="stat-total-tags">0</div>
                                <div class="stat-label">Tags</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="charts-grid">
                        <!-- Category Distribution Chart -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 class="chart-title">Activities by Category</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="category-chart"></canvas>
                            </div>
                            <div id="category-empty" class="chart-empty hidden">
                                <p>No categories yet. Create categories to see distribution.</p>
                            </div>
                        </div>

                        <!-- Tag Distribution Chart -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3 class="chart-title">Activities by Tag</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="tag-chart"></canvas>
                            </div>
                            <div id="tag-empty" class="chart-empty hidden">
                                <p>No tags yet. Create tags to see distribution.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Chart -->
                    <div class="chart-card chart-card-full">
                        <div class="chart-header">
                            <h3 class="chart-title">Activity Timeline (Last 30 Days)</h3>
                        </div>
                        <div class="chart-container chart-container-timeline">
                            <canvas id="timeline-chart"></canvas>
                        </div>
                        <div id="timeline-empty" class="chart-empty hidden">
                            <p>No activities in the last 30 days.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management View -->
            <div id="view-manage" class="view-content" role="tabpanel" aria-labelledby="tab-manage" aria-hidden="true">
                <div class="management-container">
                    <!-- Categories Management -->
                    <div class="management-section" role="region" aria-labelledby="categories-heading">
                        <div class="management-header">
                            <h2 class="management-title" id="categories-heading">Categories</h2>
                            <button id="add-category-btn" class="btn btn-primary btn-add" aria-label="Add new category">
                                <span class="btn-icon" aria-hidden="true">+</span> Add Category
                            </button>
                        </div>
                        <div id="categories-list" class="management-list" role="list" aria-label="Categories list">
                            <!-- Categories will be populated dynamically -->
                        </div>
                        <div id="categories-empty" class="management-empty hidden" role="status">
                            <p>No categories yet. Create your first category!</p>
                        </div>
                    </div>

                    <!-- Tags Management -->
                    <div class="management-section" role="region" aria-labelledby="tags-heading">
                        <div class="management-header">
                            <h2 class="management-title" id="tags-heading">Tags</h2>
                            <button id="add-tag-btn" class="btn btn-primary btn-add" aria-label="Add new tag">
                                <span class="btn-icon" aria-hidden="true">+</span> Add Tag
                            </button>
                        </div>
                        <div id="tags-list" class="management-list" role="list" aria-label="Tags list">
                            <!-- Tags will be populated dynamically -->
                        </div>
                        <div id="tags-empty" class="management-empty hidden" role="status">
                            <p>No tags yet. Create your first tag!</p>
                        </div>
                    </div>
                </div>
            </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
