# Tag Management Implementation

## Overview

This document describes the implementation of tag management functionality in OmniTrackr. Tags are customizable status labels that can be applied to activities (e.g., Todo, Completed, Doing, Planned).

## Implementation Date

December 4, 2024

## Requirements Addressed

This implementation addresses the following requirements from the specification:

- **Requirement 4.1**: Create new tags with unique names
- **Requirement 4.2**: Edit tag names while maintaining activity associations
- **Requirement 4.3**: Delete tags with cascade handling for associated activities
- **Requirement 4.4**: Reject duplicate tag names

## Architecture

### Components

1. **Tag Model** (`src/models/Tag.php`)
   - Handles database operations for tags
   - Provides CRUD methods
   - Validates tag uniqueness per user

2. **Tag Controller** (`src/controllers/TagController.php`)
   - Business logic for tag operations
   - Input validation
   - Error handling
   - User authorization

3. **API Endpoints** (`public/api/tags/`)
   - `create.php` - Create new tags
   - `list.php` - List all tags for a user
   - `update.php` - Update existing tags
   - `delete.php` - Delete tags

## Database Schema

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

### Key Features

- **User Isolation**: Each user has their own set of tags
- **Unique Constraint**: Tag names must be unique per user
- **Default Color**: Silver (#C0C0C0) is used if no color is specified
- **Cascade Delete**: When a user is deleted, their tags are automatically removed
- **Timestamps**: Automatic tracking of creation and update times

## Tag Model Methods

### `create()`
Creates a new tag in the database.

**Returns:** Tag ID on success, false on failure

**Example:**
```php
$tag = new Tag($db);
$tag->user_id = 1;
$tag->name = "Todo";
$tag->color = "#C0C0C0";
$tag_id = $tag->create();
```

### `findById($id)`
Retrieves a tag by its ID.

**Parameters:**
- `$id` (int) - Tag ID

**Returns:** true if found, false otherwise

**Example:**
```php
$tag = new Tag($db);
if ($tag->findById(1)) {
    echo $tag->name; // "Todo"
}
```

### `getAllByUser($user_id)`
Gets all tags for a specific user, ordered by name.

**Parameters:**
- `$user_id` (int) - User ID

**Returns:** Array of tag records

**Example:**
```php
$tag = new Tag($db);
$tags = $tag->getAllByUser(1);
foreach ($tags as $t) {
    echo $t['name'] . "\n";
}
```

### `update()`
Updates a tag's name and/or color.

**Returns:** true on success, false on failure

**Example:**
```php
$tag = new Tag($db);
$tag->findById(1);
$tag->name = "Completed";
$tag->color = "#4CAF50";
$tag->update();
```

### `delete()`
Deletes a tag from the database.

**Returns:** true on success, false on failure

**Example:**
```php
$tag = new Tag($db);
$tag->findById(1);
$tag->delete();
```

### `nameExistsForUser($user_id, $name, $exclude_id = null)`
Checks if a tag name already exists for a user.

**Parameters:**
- `$user_id` (int) - User ID
- `$name` (string) - Tag name to check
- `$exclude_id` (int, optional) - Tag ID to exclude from check (for updates)

**Returns:** true if name exists, false otherwise

**Example:**
```php
$tag = new Tag($db);
if ($tag->nameExistsForUser(1, "Todo")) {
    echo "Tag name already exists";
}
```

### `countActivities()`
Counts the number of activities associated with this tag.

**Returns:** Number of activities (int)

**Example:**
```php
$tag = new Tag($db);
$tag->findById(1);
$count = $tag->countActivities();
echo "This tag is used by $count activities";
```

## Tag Controller Methods

### `create($data, $user_id)`
Creates a new tag with validation.

**Parameters:**
- `$data` (array) - Tag data (name, color)
- `$user_id` (int) - User ID

**Returns:** Response array with success status and data/error

**Validation:**
- Name is required
- Name must be unique for the user
- Color defaults to #C0C0C0 if not provided

**Example:**
```php
$controller = new TagController($db);
$result = $controller->create([
    'name' => 'Todo',
    'color' => '#FFD700'
], 1);

if ($result['success']) {
    echo "Tag created with ID: " . $result['data']['id'];
}
```

### `getAll($user_id)`
Gets all tags for a user.

**Parameters:**
- `$user_id` (int) - User ID

**Returns:** Response array with success status and data

**Example:**
```php
$controller = new TagController($db);
$result = $controller->getAll(1);
foreach ($result['data'] as $tag) {
    echo $tag['name'] . "\n";
}
```

### `get($id, $user_id)`
Gets a single tag by ID.

**Parameters:**
- `$id` (int) - Tag ID
- `$user_id` (int) - User ID

**Returns:** Response array with success status and data/error

**Authorization:** Verifies tag belongs to user

**Example:**
```php
$controller = new TagController($db);
$result = $controller->get(1, 1);
if ($result['success']) {
    echo $result['data']['name'];
}
```

### `update($id, $data, $user_id)`
Updates a tag.

**Parameters:**
- `$id` (int) - Tag ID
- `$data` (array) - Updated tag data (name, color)
- `$user_id` (int) - User ID

**Returns:** Response array with success status and data/error

**Validation:**
- Tag must exist
- Tag must belong to user
- Name cannot be empty if provided
- Name must be unique for the user (excluding current tag)

**Example:**
```php
$controller = new TagController($db);
$result = $controller->update(1, [
    'name' => 'Completed',
    'color' => '#4CAF50'
], 1);
```

### `delete($id, $user_id)`
Deletes a tag.

**Parameters:**
- `$id` (int) - Tag ID
- `$user_id` (int) - User ID

**Returns:** Response array with success status and data/error

**Authorization:** Verifies tag belongs to user

**Cascade Behavior:** Database foreign key constraints handle removal of tag associations from activities

**Example:**
```php
$controller = new TagController($db);
$result = $controller->delete(1, 1);
```

## API Endpoints

### POST /api/tags/create.php

Creates a new tag.

**Authentication:** Required

**Request:**
```json
{
  "name": "Todo",
  "color": "#C0C0C0"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Todo",
    "color": "#C0C0C0"
  }
}
```

### GET /api/tags/list.php

Lists all tags for the authenticated user.

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Todo",
      "color": "#C0C0C0",
      "created_at": "2024-12-04 10:00:00",
      "updated_at": "2024-12-04 10:00:00"
    }
  ]
}
```

### PUT /api/tags/update.php?id=1

Updates an existing tag.

**Authentication:** Required

**Request:**
```json
{
  "name": "Completed",
  "color": "#4CAF50"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Completed",
    "color": "#4CAF50"
  }
}
```

### DELETE /api/tags/delete.php?id=1

Deletes a tag.

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Tag deleted successfully"
  }
}
```

## Error Handling

### Common Error Responses

**Validation Error (400):**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Tag name is required",
    "details": {}
  }
}
```

**Duplicate Name (409):**
```json
{
  "success": false,
  "error": {
    "code": "DUPLICATE_NAME",
    "message": "Tag name already exists",
    "details": {}
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Tag not found",
    "details": {}
  }
}
```

**Forbidden (403):**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Access denied",
    "details": {}
  }
}
```

## Security Features

1. **Authentication Required**: All endpoints require valid session
2. **User Isolation**: Users can only access their own tags
3. **Authorization Checks**: Verify tag ownership before operations
4. **Input Validation**: Validate all user inputs
5. **SQL Injection Prevention**: Use prepared statements
6. **Unique Constraints**: Database-level enforcement of unique names per user

## Testing

### Verification Test

A verification test is available at `tests/verify_tag_implementation.php` that tests:

1. Tag creation
2. Tag listing
3. Single tag retrieval
4. Tag updates
5. Duplicate name rejection
6. Tag deletion
7. Verification of deletion

**Run the test:**
```bash
php tests/verify_tag_implementation.php
```

### Property-Based Tests

The following property-based tests validate tag functionality:

- **Property 6**: Entity creation uniqueness (validates Requirement 4.1)
- **Property 7**: Entity rename association preservation (validates Requirement 4.2)
- **Property 8**: Entity deletion cascade (validates Requirement 4.3)
- **Property 9**: Duplicate name rejection (validates Requirement 4.4)

These tests are shared with category management since tags and categories have similar behavior.

## Usage Examples

### Creating a Tag

```javascript
// Frontend JavaScript example
fetch('/api/tags/create.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + sessionId
  },
  body: JSON.stringify({
    name: 'Todo',
    color: '#FFD700'
  })
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    console.log('Tag created:', data.data);
  } else {
    console.error('Error:', data.error.message);
  }
});
```

### Listing Tags

```javascript
fetch('/api/tags/list.php', {
  headers: {
    'Authorization': 'Bearer ' + sessionId
  }
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    data.data.forEach(tag => {
      console.log(`${tag.name} (${tag.color})`);
    });
  }
});
```

### Updating a Tag

```javascript
fetch('/api/tags/update.php?id=1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + sessionId
  },
  body: JSON.stringify({
    name: 'Completed',
    color: '#4CAF50'
  })
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    console.log('Tag updated:', data.data);
  }
});
```

### Deleting a Tag

```javascript
fetch('/api/tags/delete.php?id=1', {
  method: 'DELETE',
  headers: {
    'Authorization': 'Bearer ' + sessionId
  }
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    console.log('Tag deleted');
  }
});
```

## Relationship with Activities

Tags have a many-to-many relationship with activities through the `activity_tags` junction table:

```sql
CREATE TABLE activity_tags (
    activity_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (activity_id, tag_id),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

**Cascade Behavior:**
- When a tag is deleted, all entries in `activity_tags` referencing that tag are automatically removed
- Activities themselves are not deleted, only the tag associations
- This is handled by the database foreign key constraint `ON DELETE CASCADE`

## Future Enhancements

Potential improvements for future versions:

1. **Tag Icons**: Add icon support for visual identification
2. **Tag Groups**: Organize tags into groups or categories
3. **Tag Templates**: Provide default tag sets for common workflows
4. **Tag Statistics**: Show usage statistics for each tag
5. **Tag Suggestions**: Auto-suggest tags based on activity content
6. **Tag Colors**: Predefined color palette for consistency
7. **Tag Ordering**: Custom sort order for tags
8. **Tag Archiving**: Archive unused tags instead of deleting

## Related Documentation

- API Reference: `docs/API_REFERENCE.md`
- Category Implementation: `docs/CATEGORY_IMPLEMENTATION.md`
- Database Setup: `database/setup.sql`
- Requirements: `.kiro/specs/omnitrackr/requirements.md`
- Design: `.kiro/specs/omnitrackr/design.md`
