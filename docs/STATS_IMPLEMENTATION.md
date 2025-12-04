# Statistics and Analytics Implementation

## Overview

The statistics and analytics feature provides aggregated data about activities, categories, and tags for visualization and reporting purposes.

## Implementation Details

### Controller: StatsController

Location: `src/controllers/StatsController.php`

The StatsController provides four main endpoints for retrieving statistical data:

#### 1. Overview Statistics
- **Method**: `getOverview($user_id)`
- **Returns**: Total counts for activities, categories, and tags
- **Response Structure**:
```json
{
  "success": true,
  "data": {
    "total_activities": 42,
    "total_categories": 5,
    "total_tags": 8
  }
}
```

#### 2. Category Breakdown
- **Method**: `getCategoryBreakdown($user_id)`
- **Returns**: Activity count grouped by category
- **Response Structure**:
```json
{
  "success": true,
  "data": [
    {
      "category_id": 1,
      "category_name": "Work",
      "category_color": "#FFD700",
      "activity_count": 15
    },
    {
      "category_id": null,
      "category_name": "Uncategorized",
      "category_color": "#CCCCCC",
      "activity_count": 3
    }
  ]
}
```

#### 3. Tag Distribution
- **Method**: `getTagDistribution($user_id)`
- **Returns**: Activity count grouped by tag
- **Response Structure**:
```json
{
  "success": true,
  "data": [
    {
      "tag_id": 1,
      "tag_name": "Todo",
      "tag_color": "#C0C0C0",
      "activity_count": 12
    },
    {
      "tag_id": 2,
      "tag_name": "Completed",
      "tag_color": "#4CAF50",
      "activity_count": 20
    }
  ]
}
```

#### 4. Timeline Data
- **Method**: `getTimeline($user_id, $days = 30)`
- **Parameters**: 
  - `$user_id`: User ID
  - `$days`: Number of days to include (default 30, max 365)
- **Returns**: Daily activity creation counts
- **Response Structure**:
```json
{
  "success": true,
  "data": [
    {
      "date": "2024-12-01",
      "count": 5
    },
    {
      "date": "2024-12-02",
      "count": 3
    }
  ]
}
```

## API Endpoints

### GET /api/stats/overview
Returns overview statistics for the authenticated user.

**Authentication**: Required

**Response**: 200 OK with overview data

### GET /api/stats/by-category
Returns category breakdown for the authenticated user.

**Authentication**: Required

**Response**: 200 OK with category breakdown data

### GET /api/stats/by-tag
Returns tag distribution for the authenticated user.

**Authentication**: Required

**Response**: 200 OK with tag distribution data

### GET /api/stats/timeline?days=30
Returns timeline data for the authenticated user.

**Authentication**: Required

**Query Parameters**:
- `days` (optional): Number of days to include (1-365, default 30)

**Response**: 200 OK with timeline data

## Property-Based Tests

### Property 14: Statistics Aggregation Accuracy
**File**: `tests/Property_14_StatisticsAggregationAccuracy_Test.php`

**Property**: For any set of activities with categories and tags, calculating statistics should produce accurate counts and distributions grouped by category and tag.

**Validates**: Requirements 7.2, 7.4

**Test Strategy**:
- Generates random sets of categories (0-5), tags (0-5), and activities (1-10)
- Tracks expected counts for each category and tag
- Verifies that all statistics endpoints return accurate counts
- Runs 100 iterations with different random data

**Status**: ✓ PASSED (100/100 iterations)

### Property 15: Visualization Data Generation
**File**: `tests/Property_15_VisualizationDataGeneration_Test.php`

**Property**: For any set of activities, accessing the stats page should generate visualization data structures containing activity metrics.

**Validates**: Requirements 7.1

**Test Strategy**:
- Generates random activities with categories and tags
- Verifies that all stats endpoints return properly structured data
- Validates data types and required fields for visualization
- Ensures all counts are non-negative integers
- Runs 100 iterations with different random data

**Status**: ✓ PASSED (100/100 iterations)

## Error Handling

All stats endpoints include comprehensive error handling:

- **Database Errors**: Wrapped in try-catch blocks, return 500 with SERVER_ERROR
- **Authentication Errors**: Handled by AuthMiddleware, return 401
- **Validation Errors**: Timeline endpoint validates days parameter (1-365)
- **Method Errors**: Only GET requests allowed, return 405 for other methods

## Database Queries

### Overview Statistics
Uses simple COUNT queries on activities, categories, and tags tables filtered by user_id.

### Category Breakdown
Uses LEFT JOIN between categories and activities with GROUP BY to count activities per category. Includes a separate query for uncategorized activities.

### Tag Distribution
Uses LEFT JOIN between tags and activity_tags with GROUP BY to count activities per tag.

### Timeline Data
Uses DATE grouping on activity creation timestamps with configurable date range.

## Usage Example

```javascript
// Fetch overview statistics
fetch('/api/stats/overview', {
  method: 'GET',
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  console.log('Total activities:', data.data.total_activities);
});

// Fetch category breakdown for pie chart
fetch('/api/stats/by-category', {
  method: 'GET',
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  // Use data.data array for chart visualization
  const chartData = data.data.map(item => ({
    label: item.category_name,
    value: item.activity_count,
    color: item.category_color
  }));
});

// Fetch timeline for last 7 days
fetch('/api/stats/timeline?days=7', {
  method: 'GET',
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  // Use data.data array for line chart
  const dates = data.data.map(item => item.date);
  const counts = data.data.map(item => item.count);
});
```

## Requirements Validation

✓ **Requirement 7.1**: Stats page displays graphical visualizations - All endpoints provide structured data suitable for visualization

✓ **Requirement 7.2**: Statistics aggregated by categories and tags - Category breakdown and tag distribution endpoints implemented

✓ **Requirement 7.4**: Displays activity counts by category and tag distribution - Overview, category breakdown, and tag distribution endpoints provide these metrics

## Future Enhancements

- Add date range filtering to category and tag endpoints
- Implement caching for frequently accessed statistics
- Add more advanced metrics (averages, trends, predictions)
- Support for custom aggregation periods (weekly, monthly)
- Export statistics as CSV or PDF
