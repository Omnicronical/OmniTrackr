# Filtering Implementation

## Overview
Implemented comprehensive filtering functionality for activities with support for category and tag filters, including multi-filter conjunction logic.

## Implementation Details

### 1. Filter Logic in Activity Model
**File**: `src/models/Activity.php`

The `getAllByUser()` method now supports filtering by:
- **Category IDs**: Activities matching ANY of the specified categories (OR logic)
- **Tag IDs**: Activities having ALL of the specified tags (AND logic)
- **Combined Filters**: Activities must match category filter AND have all specified tags (conjunction)

**Key Implementation**:
- Category filtering uses `IN` clause for OR logic
- Tag filtering uses a subquery with `HAVING COUNT(DISTINCT tag_id) = ?` to ensure ALL tags are present
- Empty filters return all user activities (clear filters functionality)

### 2. API Endpoint
**File**: `public/api/activities/list.php`

The list endpoint accepts query parameters:
- `category_ids`: Comma-separated list of category IDs (e.g., `?category_ids=1,2,3`)
- `tag_ids`: Comma-separated list of tag IDs (e.g., `?tag_ids=4,5`)
- Both can be combined: `?category_ids=1,2&tag_ids=4,5`
- No parameters returns all activities (clear filters)

### 3. Property-Based Tests

#### Property 12: Multi-filter conjunction
**File**: `tests/Property_12_MultiFilterConjunction_Test.php`
**Validates**: Requirements 6.1, 6.2, 6.3

Tests that filtering works correctly:
- Single category filter returns only activities with that category
- Multiple category filter returns activities with ANY of those categories
- Single tag filter returns only activities with that tag
- Multiple tag filter returns only activities with ALL those tags (conjunction)
- Combined category and tag filters work together (conjunction)
- Unfiltered results contain all activities

**Status**: ✓ PASSED (100 iterations)

#### Property 13: Filter clear restoration
**File**: `tests/Property_13_FilterClearRestoration_Test.php`
**Validates**: Requirements 6.4

Tests that clearing filters restores the original set:
- After applying category filter, clearing returns all activities
- After applying tag filter, clearing returns all activities
- After applying combined filters, clearing returns all activities
- Cleared results match the original unfiltered results exactly

**Status**: ✓ PASSED (100 iterations)

## Requirements Satisfied

✓ **Requirement 6.1**: Category filtering - activities can be filtered by one or more categories
✓ **Requirement 6.2**: Tag filtering - activities can be filtered by one or more tags
✓ **Requirement 6.3**: Multi-filter conjunction - multiple filters work together (AND logic)
✓ **Requirement 6.4**: Clear filters - passing empty filters returns all activities

## Usage Examples

### Filter by single category
```
GET /api/activities/list.php?category_ids=1
```

### Filter by multiple categories
```
GET /api/activities/list.php?category_ids=1,2,3
```

### Filter by single tag
```
GET /api/activities/list.php?tag_ids=5
```

### Filter by multiple tags (must have ALL)
```
GET /api/activities/list.php?tag_ids=5,6
```

### Combined filters
```
GET /api/activities/list.php?category_ids=1,2&tag_ids=5,6
```

### Clear filters (get all activities)
```
GET /api/activities/list.php
```

## Technical Notes

### Tag Filtering Logic
The tag filtering uses a subquery to ensure activities have ALL specified tags:
```sql
a.id IN (
    SELECT activity_id 
    FROM activity_tags 
    WHERE tag_id IN (?, ?, ...)
    GROUP BY activity_id 
    HAVING COUNT(DISTINCT tag_id) = ?
)
```

This ensures proper conjunction - an activity must have every tag in the filter list.

### Category Filtering Logic
Category filtering uses simpler OR logic:
```sql
a.category_id IN (?, ?, ...)
```

An activity matches if it has ANY of the specified categories.

### Performance Considerations
- Uses prepared statements for SQL injection protection
- DISTINCT clause prevents duplicate results when joining with activity_tags
- Indexes on foreign keys (user_id, category_id, activity_id, tag_id) improve query performance
