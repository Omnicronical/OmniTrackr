# OmniTrackr Design Document

## Overview

OmniTrackr is a modular activity tracking application built with a PHP backend and a modern frontend interface. The system follows a three-tier architecture with clear separation between presentation, business logic, and data persistence layers. The application emphasizes user experience through smooth animations, intuitive navigation, and a sophisticated visual design while maintaining accessibility compliance.

The core architecture supports extensibility through a flexible data model that accommodates diverse activity types without requiring schema changes. User authentication ensures data security and multi-tenancy support for future scaling.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend Layer                          │
│  (HTML/CSS/JavaScript with Animation Framework)             │
│  - Dashboard UI                                             │
│  - Activity Management Interface                            │
│  - Stats Visualization                                      │
│  - Filter Controls                                          │
└─────────────────┬───────────────────────────────────────────┘
                  │ HTTP/AJAX Requests
                  │
┌─────────────────▼───────────────────────────────────────────┐
│                   PHP Backend API Layer                     │
│  - RESTful API Endpoints                                    │
│  - Authentication Middleware                                │
│  - Request Validation                                       │
│  - Business Logic Controllers                               │
└─────────────────┬───────────────────────────────────────────┘
                  │ Database Queries
                  │
┌─────────────────▼───────────────────────────────────────────┐
│                  Data Persistence Layer                     │
│  - MySQL/MariaDB Database                                   │
│  - User Data                                                │
│  - Activities, Categories, Tags                             │
│  - Relationships and Associations                           │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

**Backend:**
- PHP 8.0+ (core requirement)
- MySQL or MariaDB for persistent storage
- Session-based authentication
- RESTful API design

**Frontend:**
- Vanilla JavaScript or lightweight framework (React/Vue.js)
- CSS3 with animations and transitions
- Chart.js or similar for statistics visualization
- Fetch API for backend communication

**Deployment:**
- EC2 instance hosting
- Apache or Nginx web server
- No Docker requirement
- Simple file-based deployment

## Components and Interfaces

### Backend Components

#### 1. API Router
- Routes incoming HTTP requests to appropriate controllers
- Handles CORS and request method validation
- Applies authentication middleware to protected routes

#### 2. Authentication Controller
- Manages user registration, login, and logout
- Handles password hashing (bcrypt)
- Creates and validates session tokens
- Endpoints: `/api/auth/register`, `/api/auth/login`, `/api/auth/logout`

#### 3. Activity Controller
- CRUD operations for activities
- Endpoints:
  - `POST /api/activities` - Create activity
  - `GET /api/activities` - List activities (with optional filters)
  - `GET /api/activities/{id}` - Get single activity
  - `PUT /api/activities/{id}` - Update activity
  - `DELETE /api/activities/{id}` - Delete activity

#### 4. Category Controller
- CRUD operations for categories
- Endpoints:
  - `POST /api/categories` - Create category
  - `GET /api/categories` - List categories
  - `PUT /api/categories/{id}` - Update category
  - `DELETE /api/categories/{id}` - Delete category

#### 5. Tag Controller
- CRUD operations for tags
- Endpoints:
  - `POST /api/tags` - Create tag
  - `GET /api/tags` - List tags
  - `PUT /api/tags/{id}` - Update tag
  - `DELETE /api/tags/{id}` - Delete tag

#### 6. Stats Controller
- Aggregates activity data for analytics
- Endpoints:
  - `GET /api/stats/overview` - General statistics
  - `GET /api/stats/by-category` - Category breakdown
  - `GET /api/stats/by-tag` - Tag distribution
  - `GET /api/stats/timeline` - Activity timeline data

### Frontend Components

#### 1. Dashboard View
- Displays activity cards in a responsive grid
- Shows category and tag badges
- Provides quick action buttons (edit, delete)
- Implements smooth scroll and card animations

#### 2. Activity Form Component
- Reusable form for creating and editing activities
- Dynamic category and tag selection
- Form validation with visual feedback
- Smooth transition animations

#### 3. Filter Panel Component
- Multi-select category filters
- Multi-select tag filters
- Clear filters button
- Real-time filter application with animations

#### 4. Stats Dashboard View
- Chart components for visualizations
- Category distribution pie/donut chart
- Tag distribution bar chart
- Activity timeline graph
- Animated chart rendering

#### 5. Navigation Component
- Main navigation menu
- User account dropdown
- Smooth transitions between views
- Active state indicators

## Data Models

### User Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Category Table
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#FFD700',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name)
);
```

### Tag Table
```sql
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#C0C0C0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tag (user_id, name)
);
```

### Activity Table
```sql
CREATE TABLE activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

### Activity_Tags Junction Table
```sql
CREATE TABLE activity_tags (
    activity_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (activity_id, tag_id),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

### Session Table
```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Activity creation persistence
*For any* valid activity with required fields, creating the activity should result in it being retrievable from the database with all specified properties intact.
**Validates: Requirements 1.1**

### Property 2: Category and tag association integrity
*For any* activity, category, and set of tags, assigning them to the activity should result in all associations being correctly stored and retrievable.
**Validates: Requirements 1.2, 1.3**

### Property 3: Optional field defaults
*For any* activity created without optional fields, the system should assign default values that can be retrieved.
**Validates: Requirements 1.4**

### Property 4: Activity update preservation
*For any* activity and any valid property modifications, updating the activity should persist the new values while maintaining the activity's identity and unmodified properties.
**Validates: Requirements 2.1, 2.4**

### Property 5: Activity deletion cascade
*For any* activity with associations, deleting the activity should remove it and all its associations from the database, with no orphaned references remaining.
**Validates: Requirements 2.2**

### Property 6: Entity creation uniqueness
*For any* user, creating multiple entities (categories or tags) should result in each having a unique identifier within that user's scope.
**Validates: Requirements 1.5, 3.1, 4.1**

### Property 7: Entity rename association preservation
*For any* category or tag with associated activities, renaming the entity should update its name while maintaining all existing activity associations.
**Validates: Requirements 3.2, 4.2**

### Property 8: Entity deletion cascade
*For any* category or tag with associated activities, deleting the entity should remove it from the database and appropriately update all associated activities.
**Validates: Requirements 3.3, 4.3**

### Property 9: Duplicate name rejection
*For any* user with an existing category or tag name, attempting to create another entity with the same name should be rejected with an error.
**Validates: Requirements 3.4, 4.4**

### Property 10: User activity isolation
*For any* user with an active session, all activity operations should only access and modify data associated with that user's account, never exposing other users' data.
**Validates: Requirements 5.1, 8.4**

### Property 11: Activity display completeness
*For any* activity with category and tags, rendering the activity should include all key properties: title, description, category, and all associated tags.
**Validates: Requirements 5.3**

### Property 12: Multi-filter conjunction
*For any* set of activities and any combination of category and tag filters, applying the filters should return only activities that match all selected criteria.
**Validates: Requirements 6.1, 6.2, 6.3**

### Property 13: Filter clear restoration
*For any* set of activities, applying filters then clearing all filters should return the complete original set of activities.
**Validates: Requirements 6.4**

### Property 14: Statistics aggregation accuracy
*For any* set of activities with categories and tags, calculating statistics should produce accurate counts and distributions grouped by category and tag.
**Validates: Requirements 7.2, 7.4**

### Property 15: Visualization data generation
*For any* set of activities, accessing the stats page should generate visualization data structures containing activity metrics.
**Validates: Requirements 7.1**

### Property 16: User registration with encryption
*For any* valid credentials, registering a new user should create an account with the password stored in encrypted form, never as plaintext.
**Validates: Requirements 8.1**

### Property 17: Authentication success with valid credentials
*For any* registered user, logging in with correct credentials should authenticate the user and establish a valid session.
**Validates: Requirements 8.2**

### Property 18: Authentication failure with invalid credentials
*For any* registered user, attempting to log in with incorrect credentials should reject authentication and return an error without establishing a session.
**Validates: Requirements 8.3**

### Property 19: Session termination on logout
*For any* active user session, logging out should terminate the session such that subsequent requests require re-authentication.
**Validates: Requirements 8.5**

### Property 20: Reduced motion preference respect
*For any* user with reduced motion preferences enabled, the system should disable or minimize animations throughout the interface.
**Validates: Requirements 9.5**

### Property 21: Data persistence across restarts
*For any* user data, activities, categories, and tags, restarting the application should retain all data without loss.
**Validates: Requirements 12.3**

### Property 22: Database error handling
*For any* database operation failure, the system should handle the error gracefully and return an appropriate error response without crashing.
**Validates: Requirements 12.4**

## Error Handling

### API Error Responses

All API endpoints will return consistent error response structures:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message",
        "details": {}
    }
}
```

### Error Categories

1. **Validation Errors (400)**
   - Missing required fields
   - Invalid data formats
   - Duplicate names
   - Invalid references

2. **Authentication Errors (401)**
   - Invalid credentials
   - Expired session
   - Missing authentication token

3. **Authorization Errors (403)**
   - Attempting to access another user's data
   - Insufficient permissions

4. **Not Found Errors (404)**
   - Activity, category, or tag not found
   - User not found

5. **Server Errors (500)**
   - Database connection failures
   - Unexpected exceptions
   - Data integrity violations

### Frontend Error Handling

- Display user-friendly error messages
- Provide actionable feedback for validation errors
- Implement retry mechanisms for network failures
- Log errors for debugging purposes
- Graceful degradation when features are unavailable

### Database Error Handling

- Wrap all database operations in try-catch blocks
- Use transactions for multi-step operations
- Implement connection pooling with retry logic
- Log database errors with context
- Return appropriate HTTP status codes

## Testing Strategy

### Unit Testing

The application will use PHPUnit for backend unit testing and Jest (or similar) for frontend unit testing.

**Backend Unit Tests:**
- Test individual controller methods with mocked database connections
- Test validation logic for user inputs
- Test password hashing and session management
- Test error handling for edge cases
- Test API response formatting

**Frontend Unit Tests:**
- Test component rendering with various props
- Test form validation logic
- Test filter logic and state management
- Test data transformation functions
- Test error handling in API calls

**Key Unit Test Examples:**
- Test that attempting to edit a non-existent activity returns an error (Requirements 2.3)
- Test that database connectivity is verified on startup (Requirements 11.2, 11.3, 11.4)
- Test that setup initializes required database tables (Requirements 11.3)

### Property-Based Testing

The application will use a property-based testing library appropriate for the chosen language:
- **PHP Backend**: Use PHPUnit with a property-based testing extension, or implement custom generators
- **JavaScript Frontend**: Use fast-check or jsverify

**Property-Based Testing Configuration:**
- Each property-based test MUST run a minimum of 100 iterations
- Each test MUST be tagged with a comment referencing the correctness property from this design document
- Tag format: `**Feature: omnitrackr, Property {number}: {property_text}**`
- Each correctness property MUST be implemented by a SINGLE property-based test

**Property Test Implementation Guidelines:**
- Generate random valid inputs for activities, categories, tags, and users
- Test invariants that should hold across all valid inputs
- Verify round-trip properties (e.g., create then retrieve)
- Test that operations maintain data integrity
- Verify that filters correctly subset data
- Test that aggregations produce accurate results

**Example Property Test Structure:**
```php
/**
 * Feature: omnitrackr, Property 1: Activity creation persistence
 * Validates: Requirements 1.1
 */
public function testActivityCreationPersistence() {
    // Run 100 iterations with random activity data
    for ($i = 0; $i < 100; $i++) {
        $activity = $this->generateRandomActivity();
        $createdId = $this->activityController->create($activity);
        $retrieved = $this->activityController->get($createdId);
        $this->assertEquals($activity, $retrieved);
    }
}
```

### Integration Testing

- Test complete user workflows (register → login → create activity → filter → view stats)
- Test API endpoints with real database connections
- Test authentication flow end-to-end
- Test data persistence across application restarts
- Test cascade deletion behavior

### Accessibility Testing

- Verify WCAG 2.1 AA compliance for color contrast
- Test keyboard navigation throughout the application
- Verify screen reader compatibility
- Test with reduced motion preferences enabled
- Validate semantic HTML structure

### Performance Testing

- Measure page load times
- Test animation frame rates
- Verify database query performance with large datasets
- Test API response times under load
- Monitor memory usage during extended sessions

## UI/UX Design Specifications

### Color Palette

**Primary Colors:**
- White: `#FFFFFF` - Background, cards
- Light Grey: `#F5F5F5` - Secondary backgrounds
- Medium Grey: `#CCCCCC` - Borders, dividers
- Dark Grey: `#333333` - Text, icons
- Gold: `#FFD700` - Accents, highlights, primary actions
- Muted Gold: `#D4AF37` - Hover states, secondary accents

**Semantic Colors:**
- Success: `#4CAF50` (green)
- Warning: `#FF9800` (orange)
- Error: `#F44336` (red)
- Info: `#2196F3` (blue)

### Typography

- **Headings**: Sans-serif font (e.g., Inter, Roboto, or system font stack)
- **Body**: Same sans-serif font for consistency
- **Font Sizes**:
  - H1: 2.5rem (40px)
  - H2: 2rem (32px)
  - H3: 1.5rem (24px)
  - Body: 1rem (16px)
  - Small: 0.875rem (14px)

### Animation Specifications

**Timing Functions:**
- Ease-out for entrances: `cubic-bezier(0.0, 0.0, 0.2, 1)`
- Ease-in for exits: `cubic-bezier(0.4, 0.0, 1, 1)`
- Ease-in-out for transitions: `cubic-bezier(0.4, 0.0, 0.2, 1)`

**Duration Guidelines:**
- Micro-interactions: 200ms
- Component transitions: 300ms
- Page transitions: 400ms
- Maximum duration: 500ms

**Animation Types:**
- Fade in/out for modals and overlays
- Slide in/out for side panels and drawers
- Scale for button presses and card interactions
- Smooth scroll for navigation
- Stagger for list item animations

**Accessibility Considerations:**
- Respect `prefers-reduced-motion` media query
- Provide instant alternatives for all animations
- Ensure animations don't cause vestibular issues
- Keep motion subtle and purposeful

### Layout and Spacing

**Spacing Scale (8px base):**
- xs: 4px
- sm: 8px
- md: 16px
- lg: 24px
- xl: 32px
- 2xl: 48px

**Border Radius:**
- Small elements (buttons, badges): 4px
- Medium elements (cards, inputs): 8px
- Large elements (modals, panels): 12px

**Shadows (for depth):**
- Level 1: `0 1px 3px rgba(0,0,0,0.12)`
- Level 2: `0 4px 6px rgba(0,0,0,0.1)`
- Level 3: `0 10px 20px rgba(0,0,0,0.15)`

### Responsive Design

**Breakpoints:**
- Mobile: < 768px
- Tablet: 768px - 1024px
- Desktop: > 1024px

**Layout Behavior:**
- Mobile: Single column, stacked navigation
- Tablet: Two columns where appropriate, collapsible sidebar
- Desktop: Multi-column grid, persistent sidebar

## Security Considerations

### Authentication Security

- Use bcrypt for password hashing with appropriate cost factor (10-12)
- Implement session timeout (e.g., 24 hours)
- Use secure, httpOnly cookies for session tokens
- Implement CSRF protection for state-changing operations
- Rate limit login attempts to prevent brute force attacks

### Data Security

- Validate and sanitize all user inputs
- Use prepared statements for all database queries to prevent SQL injection
- Implement proper authorization checks on all API endpoints
- Ensure users can only access their own data
- Log security-relevant events (failed logins, unauthorized access attempts)

### API Security

- Implement authentication middleware for protected routes
- Validate request payloads against expected schemas
- Return appropriate error codes without leaking sensitive information
- Implement rate limiting to prevent abuse
- Use HTTPS in production (configured at web server level)

## Deployment Architecture

### EC2 Instance Setup

**Server Requirements:**
- Ubuntu 20.04 LTS or similar
- PHP 8.0+ with required extensions (mysqli, pdo, json)
- MySQL 8.0+ or MariaDB 10.5+
- Apache 2.4+ or Nginx 1.18+
- SSL certificate for HTTPS (Let's Encrypt recommended)

**Directory Structure:**
```
/var/www/omnitrackr/
├── public/              # Web root
│   ├── index.php       # Entry point
│   ├── css/
│   ├── js/
│   └── assets/
├── src/                # PHP source code
│   ├── controllers/
│   ├── models/
│   ├── middleware/
│   └── config/
├── database/           # Database migrations and seeds
└── .env               # Environment configuration
```

**Setup Process:**
1. Clone repository to EC2 instance
2. Run database setup script to create tables
3. Configure environment variables (.env file)
4. Set appropriate file permissions
5. Configure web server virtual host
6. Enable SSL certificate
7. Test application functionality

**Environment Configuration:**
```
DB_HOST=localhost
DB_NAME=omnitrackr
DB_USER=omnitrackr_user
DB_PASSWORD=secure_password
SESSION_LIFETIME=86400
APP_ENV=production
```

### Database Setup

**Initial Setup Script:**
```sql
-- Create database
CREATE DATABASE IF NOT EXISTS omnitrackr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER IF NOT EXISTS 'omnitrackr_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON omnitrackr.* TO 'omnitrackr_user'@'localhost';
FLUSH PRIVILEGES;

-- Run table creation scripts (from Data Models section)
```

### Backup and Maintenance

- Implement automated daily database backups
- Store backups in separate location (S3 or separate volume)
- Monitor disk space and database size
- Implement log rotation for application logs
- Schedule regular security updates for server packages

## Future Enhancements

### Phase 2 Features (Post-MVP)

1. **Advanced Statistics**
   - Time-series analysis
   - Trend predictions
   - Custom date range filtering
   - Export statistics as PDF/CSV

2. **Collaboration Features**
   - Share activities with other users
   - Team workspaces
   - Activity comments and discussions

3. **Mobile Application**
   - Native iOS and Android apps
   - Offline support with sync
   - Push notifications

4. **Integrations**
   - Calendar integration (Google Calendar, Outlook)
   - Project management tools (Jira, Trello)
   - Webhook support for automation

5. **Advanced Customization**
   - Custom activity fields
   - Activity templates
   - Custom color themes
   - Configurable dashboard layouts

### Scalability Considerations

- Implement caching layer (Redis) for frequently accessed data
- Consider database read replicas for scaling reads
- Implement API rate limiting and request queuing
- Consider CDN for static assets
- Implement database indexing optimization for large datasets
