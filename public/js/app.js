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
    
    // Initialize dashboard
    initDashboard();
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

/**
 * Dashboard State
 */
const DashboardState = {
    activities: [],
    categories: [],
    tags: [],
    selectedCategories: new Set(),
    selectedTags: new Set(),
    currentActivity: null,
    currentView: 'activities'
};

/**
 * Initialize Dashboard
 */
function initDashboard() {
    console.log('Initializing dashboard...');
    
    // Set up event listeners
    setupDashboardEventListeners();
    
    // Load initial data
    loadDashboardData();
}

/**
 * Set up dashboard event listeners
 */
function setupDashboardEventListeners() {
    // View tabs
    document.getElementById('tab-activities').addEventListener('click', () => switchView('activities'));
    document.getElementById('tab-manage').addEventListener('click', () => switchView('manage'));
    
    // Add activity button
    document.getElementById('add-activity-btn').addEventListener('click', () => {
        openActivityModal();
    });
    
    // Modal close buttons
    document.getElementById('close-modal-btn').addEventListener('click', closeActivityModal);
    document.getElementById('cancel-activity-btn').addEventListener('click', closeActivityModal);
    
    // Modal overlay click (only close when clicking the overlay itself)
    document.querySelector('.modal-overlay').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            closeActivityModal();
        }
    });
    
    // Keyboard support for modal (Escape key)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('activity-modal');
            if (!modal.classList.contains('hidden')) {
                closeActivityModal();
            }
        }
    });
    
    // Activity form submission
    document.getElementById('activity-form').addEventListener('submit', handleActivitySubmit);
    
    // Clear filters button
    document.getElementById('clear-filters-btn').addEventListener('click', clearAllFilters);
    
    // Management buttons
    document.getElementById('add-category-btn').addEventListener('click', () => showInlineForm('category'));
    document.getElementById('add-tag-btn').addEventListener('click', () => showInlineForm('tag'));
}

/**
 * Load dashboard data
 */
async function loadDashboardData() {
    showLoadingState();
    
    try {
        // Load categories, tags, and activities in parallel
        const [categoriesData, tagsData, activitiesData] = await Promise.all([
            API.get('/categories/list'),
            API.get('/tags/list'),
            API.get('/activities/list')
        ]);
        
        // Update state
        DashboardState.categories = categoriesData.data || [];
        DashboardState.tags = tagsData.data || [];
        DashboardState.activities = activitiesData.data || [];
        
        // Render UI
        renderFilters();
        renderActivities();
        populateCategoryDropdown();
        populateTagSelector();
        
        hideLoadingState();
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
        hideLoadingState();
        showEmptyState();
    }
}

/**
 * Render filter panel
 */
function renderFilters() {
    // Render category filters
    const categoryFiltersContainer = document.getElementById('category-filters');
    categoryFiltersContainer.innerHTML = '';
    
    if (DashboardState.categories.length === 0) {
        categoryFiltersContainer.innerHTML = '<p class="filter-empty">No categories yet</p>';
    } else {
        DashboardState.categories.forEach(category => {
            const filterEl = createFilterCheckbox(category, 'category');
            categoryFiltersContainer.appendChild(filterEl);
        });
    }
    
    // Render tag filters
    const tagFiltersContainer = document.getElementById('tag-filters');
    tagFiltersContainer.innerHTML = '';
    
    if (DashboardState.tags.length === 0) {
        tagFiltersContainer.innerHTML = '<p class="filter-empty">No tags yet</p>';
    } else {
        DashboardState.tags.forEach(tag => {
            const filterEl = createFilterCheckbox(tag, 'tag');
            tagFiltersContainer.appendChild(filterEl);
        });
    }
}

/**
 * Create filter checkbox element
 */
function createFilterCheckbox(item, type) {
    const label = document.createElement('label');
    label.className = 'filter-checkbox';
    
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.value = item.id;
    checkbox.addEventListener('change', (e) => {
        handleFilterChange(item.id, type, e.target.checked);
    });
    
    const span = document.createElement('span');
    span.textContent = item.name;
    
    label.appendChild(checkbox);
    label.appendChild(span);
    
    return label;
}

/**
 * Handle filter change
 */
function handleFilterChange(id, type, checked) {
    const filterSet = type === 'category' ? DashboardState.selectedCategories : DashboardState.selectedTags;
    
    if (checked) {
        filterSet.add(parseInt(id));
    } else {
        filterSet.delete(parseInt(id));
    }
    
    // Update checkbox parent styling
    const checkbox = document.querySelector(`input[value="${id}"]`);
    if (checkbox) {
        const label = checkbox.closest('.filter-checkbox');
        if (checked) {
            label.classList.add('active');
        } else {
            label.classList.remove('active');
        }
    }
    
    // Re-render activities with filters
    renderActivities();
}

/**
 * Clear all filters
 */
function clearAllFilters() {
    DashboardState.selectedCategories.clear();
    DashboardState.selectedTags.clear();
    
    // Uncheck all checkboxes
    document.querySelectorAll('.filter-checkbox input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
        cb.closest('.filter-checkbox').classList.remove('active');
    });
    
    // Re-render activities
    renderActivities();
}

/**
 * Render activities grid
 */
function renderActivities() {
    const grid = document.getElementById('activities-grid');
    const emptyState = document.getElementById('empty-state');
    
    // Filter activities
    const filteredActivities = filterActivities(DashboardState.activities);
    
    if (filteredActivities.length === 0) {
        grid.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    grid.innerHTML = '';
    
    // Render each activity card with stagger animation
    filteredActivities.forEach((activity, index) => {
        const card = createActivityCard(activity);
        card.style.animationDelay = `${index * 50}ms`;
        grid.appendChild(card);
    });
}

/**
 * Filter activities based on selected filters
 */
function filterActivities(activities) {
    return activities.filter(activity => {
        // Category filter
        if (DashboardState.selectedCategories.size > 0) {
            if (!activity.category_id || !DashboardState.selectedCategories.has(parseInt(activity.category_id))) {
                return false;
            }
        }
        
        // Tag filter
        if (DashboardState.selectedTags.size > 0) {
            const activityTagIds = (activity.tags || []).map(t => parseInt(t.id));
            const hasMatchingTag = activityTagIds.some(tagId => DashboardState.selectedTags.has(tagId));
            if (!hasMatchingTag) {
                return false;
            }
        }
        
        return true;
    });
}

/**
 * Create activity card element
 */
function createActivityCard(activity) {
    const card = document.createElement('div');
    card.className = 'activity-card';
    card.dataset.activityId = activity.id;
    
    // Card header
    const header = document.createElement('div');
    header.className = 'activity-card-header';
    
    const title = document.createElement('h3');
    title.className = 'activity-card-title';
    title.textContent = activity.title;
    header.appendChild(title);
    
    card.appendChild(header);
    
    // Description
    if (activity.description) {
        const description = document.createElement('p');
        description.className = 'activity-card-description';
        description.textContent = activity.description;
        card.appendChild(description);
    }
    
    // Meta information
    const meta = document.createElement('div');
    meta.className = 'activity-card-meta';
    
    // Category
    if (activity.category_name) {
        const category = document.createElement('div');
        category.className = 'activity-card-category';
        category.innerHTML = `<span>📁</span> ${activity.category_name}`;
        meta.appendChild(category);
    }
    
    // Tags
    if (activity.tags && activity.tags.length > 0) {
        const tagsContainer = document.createElement('div');
        tagsContainer.className = 'activity-card-tags';
        
        activity.tags.forEach(tag => {
            const tagEl = document.createElement('span');
            tagEl.className = 'activity-card-tag';
            tagEl.textContent = tag.name;
            tagsContainer.appendChild(tagEl);
        });
        
        meta.appendChild(tagsContainer);
    }
    
    card.appendChild(meta);
    
    // Actions
    const actions = document.createElement('div');
    actions.className = 'activity-card-actions';
    
    const editBtn = document.createElement('button');
    editBtn.className = 'btn-icon-only btn-edit';
    editBtn.innerHTML = '✏️';
    editBtn.title = 'Edit activity';
    editBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openActivityModal(activity);
    });
    
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-icon-only btn-delete';
    deleteBtn.innerHTML = '🗑️';
    deleteBtn.title = 'Delete activity';
    deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        handleDeleteActivity(activity);
    });
    
    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);
    card.appendChild(actions);
    
    return card;
}

/**
 * Open activity modal
 */
function openActivityModal(activity = null) {
    const modal = document.getElementById('activity-modal');
    const form = document.getElementById('activity-form');
    const modalTitle = document.getElementById('modal-title');
    
    // Reset form
    form.reset();
    hideError(document.getElementById('activity-form-error'));
    
    // Remove any error classes
    document.getElementById('activity-title').classList.remove('error');
    
    if (activity) {
        // Edit mode
        modalTitle.textContent = 'Edit Activity';
        document.getElementById('activity-id').value = activity.id;
        document.getElementById('activity-title').value = activity.title;
        document.getElementById('activity-description').value = activity.description || '';
        document.getElementById('activity-category').value = activity.category_id || '';
        
        // Select tags
        const tagCheckboxes = document.querySelectorAll('.tag-option input[type="checkbox"]');
        const activityTagIds = (activity.tags || []).map(t => parseInt(t.id));
        tagCheckboxes.forEach(cb => {
            const tagId = parseInt(cb.value);
            cb.checked = activityTagIds.includes(tagId);
            if (cb.checked) {
                cb.closest('.tag-option').classList.add('selected');
            } else {
                cb.closest('.tag-option').classList.remove('selected');
            }
        });
        
        DashboardState.currentActivity = activity;
    } else {
        // Create mode
        modalTitle.textContent = 'Add Activity';
        DashboardState.currentActivity = null;
    }
    
    modal.classList.remove('hidden');
    
    // Focus title field after animation starts
    setTimeout(() => {
        document.getElementById('activity-title').focus();
    }, 100);
}

/**
 * Close activity modal
 */
function closeActivityModal() {
    const modal = document.getElementById('activity-modal');
    const modalContent = modal.querySelector('.modal-content');
    
    // Add fade-out animation
    modalContent.style.animation = 'scaleOut var(--timing-medium) var(--ease-in)';
    
    // Hide modal after animation completes
    setTimeout(() => {
        modal.classList.add('hidden');
        modalContent.style.animation = '';
        DashboardState.currentActivity = null;
    }, 300);
}

/**
 * Handle activity form submission
 */
async function handleActivitySubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = document.getElementById('save-activity-btn');
    const errorDiv = document.getElementById('activity-form-error');
    
    // Get form data
    const activityId = document.getElementById('activity-id').value;
    const title = document.getElementById('activity-title').value.trim();
    const description = document.getElementById('activity-description').value.trim();
    const categoryId = document.getElementById('activity-category').value;
    
    // Get selected tags
    const selectedTags = Array.from(document.querySelectorAll('.tag-option input[type="checkbox"]:checked'))
        .map(cb => parseInt(cb.value));
    
    // Validate
    if (!title) {
        showError(errorDiv, 'Title is required');
        document.getElementById('activity-title').classList.add('error');
        document.getElementById('activity-title').focus();
        return;
    }
    
    // Remove error class if validation passes
    document.getElementById('activity-title').classList.remove('error');
    
    // Disable submit button and show loading state
    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = activityId ? 'Updating...' : 'Creating...';
    submitBtn.style.opacity = '0.7';
    hideError(errorDiv);
    
    try {
        const payload = {
            title,
            description: description || null,
            category_id: categoryId || null,
            tag_ids: selectedTags
        };
        
        let response;
        if (activityId) {
            // Update existing activity
            response = await API.put(`/activities/update?id=${activityId}`, payload);
        } else {
            // Create new activity
            response = await API.post('/activities/create', payload);
        }
        
        if (response.success) {
            // Reload activities
            await loadDashboardData();
            closeActivityModal();
        } else {
            showError(errorDiv, response.error?.message || 'Failed to save activity');
        }
    } catch (error) {
        console.error('Activity save error:', error);
        showError(errorDiv, error.message || 'An error occurred. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Activity';
        submitBtn.style.opacity = '1';
    }
}

/**
 * Handle delete activity
 */
async function handleDeleteActivity(activity) {
    if (!confirm(`Are you sure you want to delete "${activity.title}"?`)) {
        return;
    }
    
    try {
        const response = await API.delete(`/activities/delete?id=${activity.id}`);
        
        if (response.success) {
            // Reload activities
            await loadDashboardData();
        } else {
            alert('Failed to delete activity: ' + (response.error?.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Delete activity error:', error);
        alert('An error occurred while deleting the activity.');
    }
}

/**
 * Populate category dropdown
 */
function populateCategoryDropdown() {
    const select = document.getElementById('activity-category');
    
    // Clear existing options except the first one
    while (select.options.length > 1) {
        select.remove(1);
    }
    
    // Add category options
    DashboardState.categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        select.appendChild(option);
    });
}

/**
 * Populate tag selector
 */
function populateTagSelector() {
    const container = document.getElementById('activity-tags');
    container.innerHTML = '';
    
    if (DashboardState.tags.length === 0) {
        container.innerHTML = '<p style="color: var(--color-medium-grey); font-size: 0.875rem;">No tags available. Create tags first.</p>';
        return;
    }
    
    DashboardState.tags.forEach(tag => {
        const label = document.createElement('label');
        label.className = 'tag-option';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = tag.id;
        checkbox.addEventListener('change', (e) => {
            if (e.target.checked) {
                label.classList.add('selected');
            } else {
                label.classList.remove('selected');
            }
        });
        
        const span = document.createElement('span');
        span.textContent = tag.name;
        
        label.appendChild(checkbox);
        label.appendChild(span);
        
        container.appendChild(label);
    });
}

/**
 * Show loading state
 */
function showLoadingState() {
    document.getElementById('loading-state').classList.remove('hidden');
    document.getElementById('activities-grid').classList.add('hidden');
    document.getElementById('empty-state').classList.add('hidden');
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('activities-grid').classList.remove('hidden');
}

/**
 * Show empty state
 */
function showEmptyState() {
    document.getElementById('empty-state').classList.remove('hidden');
    document.getElementById('activities-grid').classList.add('hidden');
}

/**
 * Switch between views
 */
function switchView(viewName) {
    DashboardState.currentView = viewName;
    
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-view="${viewName}"]`).classList.add('active');
    
    // Update view content
    document.querySelectorAll('.view-content').forEach(view => {
        view.classList.remove('active');
    });
    document.getElementById(`view-${viewName}`).classList.add('active');
    
    // Load management data if switching to manage view
    if (viewName === 'manage') {
        renderManagementLists();
    }
}

/**
 * Render management lists
 */
function renderManagementLists() {
    renderCategoriesList();
    renderTagsList();
}

/**
 * Render categories list
 */
function renderCategoriesList() {
    const container = document.getElementById('categories-list');
    const emptyState = document.getElementById('categories-empty');
    
    // Remove any existing inline forms
    const existingForm = container.querySelector('.inline-form');
    if (existingForm) {
        existingForm.remove();
    }
    
    if (DashboardState.categories.length === 0) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    container.innerHTML = '';
    
    DashboardState.categories.forEach((category, index) => {
        const item = createManagementItem(category, 'category');
        item.style.animationDelay = `${index * 50}ms`;
        container.appendChild(item);
    });
}

/**
 * Render tags list
 */
function renderTagsList() {
    const container = document.getElementById('tags-list');
    const emptyState = document.getElementById('tags-empty');
    
    // Remove any existing inline forms
    const existingForm = container.querySelector('.inline-form');
    if (existingForm) {
        existingForm.remove();
    }
    
    if (DashboardState.tags.length === 0) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    container.innerHTML = '';
    
    DashboardState.tags.forEach((tag, index) => {
        const item = createManagementItem(tag, 'tag');
        item.style.animationDelay = `${index * 50}ms`;
        container.appendChild(item);
    });
}

/**
 * Create management item element
 */
function createManagementItem(item, type) {
    const div = document.createElement('div');
    div.className = 'management-item';
    div.dataset.id = item.id;
    div.dataset.type = type;
    
    const content = document.createElement('div');
    content.className = 'management-item-content';
    
    const name = document.createElement('span');
    name.className = 'management-item-name';
    name.textContent = item.name;
    
    content.appendChild(name);
    div.appendChild(content);
    
    const actions = document.createElement('div');
    actions.className = 'management-item-actions';
    
    const editBtn = document.createElement('button');
    editBtn.className = 'btn-icon-only';
    editBtn.innerHTML = '✏️';
    editBtn.title = `Edit ${type}`;
    editBtn.addEventListener('click', () => enableInlineEdit(div, item, type));
    
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-icon-only btn-delete';
    deleteBtn.innerHTML = '🗑️';
    deleteBtn.title = `Delete ${type}`;
    deleteBtn.addEventListener('click', () => handleDeleteEntity(item, type));
    
    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);
    div.appendChild(actions);
    
    return div;
}

/**
 * Show inline form for creating new entity
 */
function showInlineForm(type) {
    const containerId = type === 'category' ? 'categories-list' : 'tags-list';
    const container = document.getElementById(containerId);
    
    // Remove any existing inline forms
    const existingForm = container.querySelector('.inline-form');
    if (existingForm) {
        existingForm.remove();
        return;
    }
    
    const form = document.createElement('div');
    form.className = 'inline-form';
    
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = `Enter ${type} name`;
    input.required = true;
    
    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'inline-form-actions';
    
    const saveBtn = document.createElement('button');
    saveBtn.className = 'btn btn-primary btn-small';
    saveBtn.textContent = 'Save';
    saveBtn.addEventListener('click', () => handleCreateEntity(input.value.trim(), type, form));
    
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-secondary btn-small';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.addEventListener('click', () => form.remove());
    
    actionsDiv.appendChild(saveBtn);
    actionsDiv.appendChild(cancelBtn);
    
    form.appendChild(input);
    form.appendChild(actionsDiv);
    
    // Insert at the top
    container.insertBefore(form, container.firstChild);
    
    // Focus input
    setTimeout(() => input.focus(), 100);
    
    // Handle Enter key
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveBtn.click();
        } else if (e.key === 'Escape') {
            form.remove();
        }
    });
}

/**
 * Handle create entity
 */
async function handleCreateEntity(name, type, formElement) {
    if (!name) {
        alert('Name is required');
        return;
    }
    
    const endpoint = type === 'category' ? '/categories/create' : '/tags/create';
    
    try {
        const response = await API.post(endpoint, { name });
        
        if (response.success) {
            // Reload data
            await loadDashboardData();
            renderManagementLists();
            formElement.remove();
        } else {
            alert(`Failed to create ${type}: ` + (response.error?.message || 'Unknown error'));
        }
    } catch (error) {
        console.error(`Create ${type} error:`, error);
        alert(`An error occurred while creating the ${type}.`);
    }
}

/**
 * Enable inline edit for entity
 */
function enableInlineEdit(itemElement, item, type) {
    // Check if already editing
    if (itemElement.classList.contains('editing')) {
        return;
    }
    
    itemElement.classList.add('editing');
    
    const content = itemElement.querySelector('.management-item-content');
    const nameSpan = content.querySelector('.management-item-name');
    const originalName = nameSpan.textContent;
    
    // Replace name with input
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'management-item-input';
    input.value = originalName;
    
    content.innerHTML = '';
    content.appendChild(input);
    
    // Update actions
    const actions = itemElement.querySelector('.management-item-actions');
    actions.innerHTML = '';
    
    const saveBtn = document.createElement('button');
    saveBtn.className = 'btn-icon-only';
    saveBtn.innerHTML = '✅';
    saveBtn.title = 'Save';
    saveBtn.addEventListener('click', () => handleUpdateEntity(item.id, input.value.trim(), type, itemElement, originalName));
    
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn-icon-only';
    cancelBtn.innerHTML = '❌';
    cancelBtn.title = 'Cancel';
    cancelBtn.addEventListener('click', () => cancelInlineEdit(itemElement, originalName));
    
    actions.appendChild(saveBtn);
    actions.appendChild(cancelBtn);
    
    // Focus input and select text
    setTimeout(() => {
        input.focus();
        input.select();
    }, 100);
    
    // Handle Enter and Escape keys
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveBtn.click();
        } else if (e.key === 'Escape') {
            cancelInlineEdit(itemElement, originalName);
        }
    });
}

/**
 * Cancel inline edit
 */
function cancelInlineEdit(itemElement, originalName) {
    itemElement.classList.remove('editing');
    
    const content = itemElement.querySelector('.management-item-content');
    content.innerHTML = '';
    
    const name = document.createElement('span');
    name.className = 'management-item-name';
    name.textContent = originalName;
    content.appendChild(name);
    
    const actions = itemElement.querySelector('.management-item-actions');
    const type = itemElement.dataset.type;
    const id = itemElement.dataset.id;
    
    // Find the item in state
    const items = type === 'category' ? DashboardState.categories : DashboardState.tags;
    const item = items.find(i => i.id == id);
    
    actions.innerHTML = '';
    
    const editBtn = document.createElement('button');
    editBtn.className = 'btn-icon-only';
    editBtn.innerHTML = '✏️';
    editBtn.title = `Edit ${type}`;
    editBtn.addEventListener('click', () => enableInlineEdit(itemElement, item, type));
    
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-icon-only btn-delete';
    deleteBtn.innerHTML = '🗑️';
    deleteBtn.title = `Delete ${type}`;
    deleteBtn.addEventListener('click', () => handleDeleteEntity(item, type));
    
    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);
}

/**
 * Handle update entity
 */
async function handleUpdateEntity(id, newName, type, itemElement, originalName) {
    if (!newName) {
        alert('Name is required');
        return;
    }
    
    if (newName === originalName) {
        cancelInlineEdit(itemElement, originalName);
        return;
    }
    
    const endpoint = type === 'category' ? `/categories/update?id=${id}` : `/tags/update?id=${id}`;
    
    try {
        const response = await API.put(endpoint, { name: newName });
        
        if (response.success) {
            // Reload data
            await loadDashboardData();
            renderManagementLists();
        } else {
            alert(`Failed to update ${type}: ` + (response.error?.message || 'Unknown error'));
            cancelInlineEdit(itemElement, originalName);
        }
    } catch (error) {
        console.error(`Update ${type} error:`, error);
        alert(`An error occurred while updating the ${type}.`);
        cancelInlineEdit(itemElement, originalName);
    }
}

/**
 * Handle delete entity
 */
async function handleDeleteEntity(item, type) {
    if (!confirm(`Are you sure you want to delete "${item.name}"? This will affect all associated activities.`)) {
        return;
    }
    
    const endpoint = type === 'category' ? `/categories/delete?id=${item.id}` : `/tags/delete?id=${item.id}`;
    
    try {
        const response = await API.delete(endpoint);
        
        if (response.success) {
            // Reload data
            await loadDashboardData();
            renderManagementLists();
        } else {
            alert(`Failed to delete ${type}: ` + (response.error?.message || 'Unknown error'));
        }
    } catch (error) {
        console.error(`Delete ${type} error:`, error);
        alert(`An error occurred while deleting the ${type}.`);
    }
}
