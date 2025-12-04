/**
 * OmniTrackr Main Application JavaScript
 */

// Application state
const AppState = {
    currentUser: null,
    isAuthenticated: false
};

// Application initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('OmniTrackr initialized');
    
    // Initialize authentication
    initAuth();
    
    // Test API health endpoint
    testAPIConnection();
});

/**
 * Initialize authentication system
 */
function initAuth() {
    // Check if user is already logged in
    checkAuthStatus();
    
    // Set up form event listeners
    setupAuthForms();
}

/**
 * Check authentication status
 */
async function checkAuthStatus() {
    const sessionId = getSessionId();
    
    if (sessionId) {
        // Validate session with backend
        try {
            const response = await fetch('/api/auth/validate', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.user) {
                    // User is authenticated
                    AppState.currentUser = data.user;
                    AppState.isAuthenticated = true;
                    showMainApp();
                    return;
                }
            }
        } catch (error) {
            console.error('Session validation failed:', error);
        }
    }
    
    // Show authentication forms
    showAuthForms();
}

/**
 * Set up authentication form handlers
 */
function setupAuthForms() {
    // Login form
    const loginForm = document.getElementById('login-form');
    loginForm.addEventListener('submit', handleLogin);
    
    // Register form
    const registerForm = document.getElementById('register-form');
    registerForm.addEventListener('submit', handleRegister);
    
    // Form switchers
    document.getElementById('show-register').addEventListener('click', (e) => {
        e.preventDefault();
        showRegisterForm();
    });
    
    document.getElementById('show-login').addEventListener('click', (e) => {
        e.preventDefault();
        showLoginForm();
    });
    
    // Logout button
    document.getElementById('logout-btn').addEventListener('click', handleLogout);
}

/**
 * Handle login form submission
 */
async function handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('login-error');
    
    // Get form data
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    
    // Validate
    if (!username || !password) {
        showError(errorDiv, 'Please enter both username and password');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';
    hideError(errorDiv);
    
    try {
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store session
            if (data.session_id) {
                setSessionId(data.session_id);
            }
            
            // Update app state
            AppState.currentUser = data.user;
            AppState.isAuthenticated = true;
            
            // Show main app
            showMainApp();
        } else {
            showError(errorDiv, data.error?.message || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        showError(errorDiv, 'An error occurred. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
    }
}

/**
 * Handle registration form submission
 */
async function handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('register-error');
    
    // Get form data
    const username = document.getElementById('register-username').value.trim();
    const email = document.getElementById('register-email').value.trim();
    const password = document.getElementById('register-password').value;
    const passwordConfirm = document.getElementById('register-password-confirm').value;
    
    // Validate
    if (!username || !email || !password || !passwordConfirm) {
        showError(errorDiv, 'Please fill in all fields');
        return;
    }
    
    if (username.length < 3) {
        showError(errorDiv, 'Username must be at least 3 characters');
        return;
    }
    
    if (password.length < 6) {
        showError(errorDiv, 'Password must be at least 6 characters');
        return;
    }
    
    if (password !== passwordConfirm) {
        showError(errorDiv, 'Passwords do not match');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating account...';
    hideError(errorDiv);
    
    try {
        const response = await fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store session
            if (data.session_id) {
                setSessionId(data.session_id);
            }
            
            // Update app state
            AppState.currentUser = data.user;
            AppState.isAuthenticated = true;
            
            // Show main app
            showMainApp();
        } else {
            showError(errorDiv, data.error?.message || 'Registration failed');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showError(errorDiv, 'An error occurred. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Register';
    }
}

/**
 * Handle logout
 */
async function handleLogout() {
    try {
        await fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    // Clear session
    clearSessionId();
    
    // Reset app state
    AppState.currentUser = null;
    AppState.isAuthenticated = false;
    
    // Show auth forms
    showAuthForms();
}

/**
 * Show authentication forms
 */
function showAuthForms() {
    document.getElementById('auth-container').classList.remove('hidden');
    document.getElementById('main-container').classList.add('hidden');
    document.getElementById('main-nav').classList.add('hidden');
}

/**
 * Show main application
 */
function showMainApp() {
    document.getElementById('auth-container').classList.add('hidden');
    document.getElementById('main-container').classList.remove('hidden');
    document.getElementById('main-nav').classList.remove('hidden');
    
    // Update user display
    if (AppState.currentUser) {
        document.getElementById('user-display').textContent = AppState.currentUser.username;
    }
}

/**
 * Show login form
 */
function showLoginForm() {
    document.getElementById('login-form').classList.remove('hidden');
    document.getElementById('register-form').classList.add('hidden');
    hideError(document.getElementById('register-error'));
}

/**
 * Show register form
 */
function showRegisterForm() {
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('register-form').classList.remove('hidden');
    hideError(document.getElementById('login-error'));
}

/**
 * Show error message
 */
function showError(element, message) {
    element.textContent = message;
    element.classList.remove('hidden');
}

/**
 * Hide error message
 */
function hideError(element) {
    element.textContent = '';
    element.classList.add('hidden');
}

/**
 * Session management
 */
function getSessionId() {
    return localStorage.getItem('omnitrackr_session');
}

function setSessionId(sessionId) {
    localStorage.setItem('omnitrackr_session', sessionId);
}

function clearSessionId() {
    localStorage.removeItem('omnitrackr_session');
}

/**
 * Test API connection
 */
async function testAPIConnection() {
    try {
        const response = await fetch('/api/health');
        const data = await response.json();
        
        if (data.success) {
            console.log('✓ API connection successful');
            console.log('Database status:', data.message);
        } else {
            console.error('✗ API connection failed:', data.error);
        }
    } catch (error) {
        console.error('✗ Failed to connect to API:', error);
    }
}

/**
 * API helper functions (to be expanded in later tasks)
 */
const API = {
    baseURL: '/api',
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error?.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },
    
    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },
    
    post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },
    
    put(endpoint, body) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },
    
    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};
