# OmniTrackr API Reference

## Base URL

```
http://your-domain.com/api
```

## Authentication

Most endpoints require authentication. Include the session ID in one of the following ways:

1. **Cookie** (recommended for browser clients):
   ```
   Cookie: session_id=YOUR_SESSION_ID
   ```

2. **Authorization Header** (recommended for API clients):
   ```
   Authorization: Bearer YOUR_SESSION_ID
   ```

3. **Request Parameter** (fallback):
   ```
   ?session_id=YOUR_SESSION_ID
   ```

## Response Format

All API responses follow this format:

### Success Response
```json
{
  "success": true,
  "data": {
    // Response data here
  }
}
```

### Error Response
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

## HTTP Status Codes

- `200 OK` - Request succeeded
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid input or validation error
- `401 Unauthorized` - Authentication required or failed
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `405 Method Not Allowed` - Wrong HTTP method
- `500 Internal Server Error` - Server error

## Error Codes

- `VALIDATION_ERROR` - Invalid input data
- `DUPLICATE_USERNAME` - Username already exists
- `DUPLICATE_EMAIL` - Email already exists
- `INVALID_CREDENTIALS` - Wrong username or password
- `INVALID_SESSION` - Session not found or expired
- `UNAUTHORIZED` - Authentication required
- `USER_NOT_FOUND` - User does not exist
- `SERVER_ERROR` - Unexpected server error
- `INVALID_JSON` - Malformed JSON in request
- `METHOD_NOT_ALLOWED` - Wrong HTTP method used

---

## Authentication Endpoints

### Register User

Create a new user account.

**Endpoint:** `POST /api/auth/register`

**Authentication:** Not required

**Request Body:**
```json
{
  "username": "string (required, unique)",
  "email": "string (required, unique, valid email format)",
  "password": "string (required, min 6 characters)"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "username": "johndoe",
    "email": "john@example.com"
  }
}
```

**Error Responses:**

- `400 Bad Request` - Validation error
  ```json
  {
    "success": false,
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "Username, email, and password are required",
      "details": {}
    }
  }
  ```

- `400 Bad Request` - Duplicate username
  ```json
  {
    "success": false,
    "error": {
      "code": "DUPLICATE_USERNAME",
      "message": "Username already exists",
      "details": {}
    }
  }
  ```

- `400 Bad Request` - Duplicate email
  ```json
  {
    "success": false,
    "error": {
      "code": "DUPLICATE_EMAIL",
      "message": "Email already exists",
      "details": {}
    }
  }
  ```

**Example:**
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "securepass123"
  }'
```

---

### Login

Authenticate a user and create a session.

**Endpoint:** `POST /api/auth/login`

**Authentication:** Not required

**Request Body:**
```json
{
  "username": "string (required)",
  "password": "string (required)"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "session_id": "abc123def456...",
    "user_id": 1,
    "username": "johndoe",
    "email": "john@example.com",
    "expires_at": "2024-12-05 12:00:00"
  }
}
```

**Sets Cookie:**
```
Set-Cookie: session_id=abc123def456...; Path=/; HttpOnly
```

**Error Responses:**

- `401 Unauthorized` - Invalid credentials
  ```json
  {
    "success": false,
    "error": {
      "code": "INVALID_CREDENTIALS",
      "message": "Invalid username or password",
      "details": {}
    }
  }
  ```

**Example:**
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "password": "securepass123"
  }' \
  -c cookies.txt
```

---

### Logout

Terminate the current session.

**Endpoint:** `POST /api/auth/logout`

**Authentication:** Required

**Request Body:** None

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Logged out successfully"
  }
}
```

**Clears Cookie:**
```
Set-Cookie: session_id=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT
```

**Error Responses:**

- `401 Unauthorized` - Invalid or expired session
  ```json
  {
    "success": false,
    "error": {
      "code": "INVALID_SESSION",
      "message": "Invalid or expired session",
      "details": {}
    }
  }
  ```

**Example:**
```bash
# Using cookie
curl -X POST http://localhost/api/auth/logout \
  -b cookies.txt

# Using Authorization header
curl -X POST http://localhost/api/auth/logout \
  -H "Authorization: Bearer YOUR_SESSION_ID"
```

---

## Activity Endpoints

*Coming soon - Task 5*

### Create Activity
`POST /api/activities`

### List Activities
`GET /api/activities`

### Get Activity
`GET /api/activities/{id}`

### Update Activity
`PUT /api/activities/{id}`

### Delete Activity
`DELETE /api/activities/{id}`

---

## Category Endpoints

*Coming soon - Task 3*

### Create Category
`POST /api/categories`

### List Categories
`GET /api/categories`

### Update Category
`PUT /api/categories/{id}`

### Delete Category
`DELETE /api/categories/{id}`

---

## Tag Endpoints

### Create Tag

Create a new tag for the authenticated user.

**Endpoint:** `POST /api/tags/create.php`

**Authentication:** Required

**Request Body:**
```json
{
  "name": "string (required, unique per user)",
  "color": "string (optional, hex color, default: #C0C0C0)"
}
```

**Success Response (201):**
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

**Error Responses:**

- `400 Bad Request` - Validation error
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

- `409 Conflict` - Duplicate name
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

**Example:**
```bash
curl -X POST http://localhost/api/tags/create.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SESSION_ID" \
  -d '{
    "name": "Todo",
    "color": "#FFD700"
  }'
```

---

### List Tags

Get all tags for the authenticated user.

**Endpoint:** `GET /api/tags/list.php`

**Authentication:** Required

**Request Body:** None

**Success Response (200):**
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
    },
    {
      "id": 2,
      "user_id": 1,
      "name": "InProgress",
      "color": "#FFD700",
      "created_at": "2024-12-04 10:05:00",
      "updated_at": "2024-12-04 10:05:00"
    }
  ]
}
```

**Example:**
```bash
curl -X GET http://localhost/api/tags/list.php \
  -H "Authorization: Bearer YOUR_SESSION_ID"
```

---

### Update Tag

Update an existing tag.

**Endpoint:** `PUT /api/tags/update.php?id={id}`

**Authentication:** Required

**URL Parameters:**
- `id` (integer, required) - Tag ID

**Request Body:**
```json
{
  "name": "string (optional)",
  "color": "string (optional, hex color)"
}
```

**Success Response (200):**
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

**Error Responses:**

- `400 Bad Request` - Validation error
  ```json
  {
    "success": false,
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "Tag name cannot be empty",
      "details": {}
    }
  }
  ```

- `403 Forbidden` - Access denied
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

- `404 Not Found` - Tag not found
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

- `409 Conflict` - Duplicate name
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

**Example:**
```bash
curl -X PUT "http://localhost/api/tags/update.php?id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_SESSION_ID" \
  -d '{
    "name": "Completed",
    "color": "#4CAF50"
  }'
```

---

### Delete Tag

Delete a tag. This will also remove all associations with activities (cascade delete).

**Endpoint:** `DELETE /api/tags/delete.php?id={id}`

**Authentication:** Required

**URL Parameters:**
- `id` (integer, required) - Tag ID

**Request Body:** None

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Tag deleted successfully"
  }
}
```

**Error Responses:**

- `403 Forbidden` - Access denied
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

- `404 Not Found` - Tag not found
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

**Example:**
```bash
curl -X DELETE "http://localhost/api/tags/delete.php?id=1" \
  -H "Authorization: Bearer YOUR_SESSION_ID"
```

---

## Statistics Endpoints

*Coming soon - Task 7*

### Get Overview Statistics
`GET /api/stats/overview`

### Get Category Breakdown
`GET /api/stats/by-category`

### Get Tag Distribution
`GET /api/stats/by-tag`

### Get Timeline Data
`GET /api/stats/timeline`

---

## Common Patterns

### Authentication Flow

1. **Register** (if new user):
   ```bash
   POST /api/auth/register
   ```

2. **Login**:
   ```bash
   POST /api/auth/login
   # Save session_id from response
   ```

3. **Make authenticated requests**:
   ```bash
   GET /api/activities
   # Include session_id in cookie or header
   ```

4. **Logout** (when done):
   ```bash
   POST /api/auth/logout
   ```

### Error Handling

Always check the `success` field in responses:

```javascript
fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username, password })
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    // Handle success
    console.log('Logged in:', data.data);
  } else {
    // Handle error
    console.error('Error:', data.error.message);
  }
});
```

### Session Management

Sessions expire after 24 hours (configurable via `SESSION_LIFETIME` in `.env`).

To check if a session is still valid, make any authenticated request. If you receive a `401 Unauthorized` response, the session has expired and the user needs to login again.

---

## Rate Limiting

*Not yet implemented*

Future versions will include rate limiting to prevent abuse:
- Registration: 5 attempts per hour per IP
- Login: 10 attempts per hour per IP
- API calls: 1000 requests per hour per user

---

## CORS

CORS is enabled for all origins in development. In production, configure allowed origins in the API endpoint files.

Current headers:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

## Changelog

### Version 1.0.0 (Current)

**Implemented:**
- User registration
- User login
- User logout
- Session management
- Password encryption (bcrypt)
- User data isolation
- Category management (CRUD operations)
- Tag management (CRUD operations)

**Coming Soon:**
- Activity management
- Filtering
- Statistics
- Rate limiting

---

## Support

For issues or questions:
- Check the setup guide: `SETUP.md`
- Review test documentation: `tests/README.md`
- See implementation details: `AUTHENTICATION_IMPLEMENTATION.md`
