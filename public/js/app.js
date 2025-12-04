/**
 * OmniTrackr Main Application JavaScript
 */

// Application initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('OmniTrackr initialized');
    
    // Test API health endpoint
    testAPIConnection();
});

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
