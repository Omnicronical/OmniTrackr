# OmniTrackr Property-Based Tests

## Prerequisites

Before running the tests, ensure you have:

1. **PHP 8.0 or higher** installed
2. **mysqli extension** enabled in PHP
3. **MySQL/MariaDB** database running
4. **.env file** configured with database credentials

### Enabling mysqli Extension

#### Windows
1. Open `php.ini` file (usually in `C:\php\php.ini` or check with `php --ini`)
2. Find the line `;extension=mysqli`
3. Remove the semicolon to uncomment: `extension=mysqli`
4. Restart your web server or PHP

#### Linux/Mac
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli

# Mac with Homebrew
brew install php
# mysqli is usually included by default
```

### Database Setup

1. Create the database and tables:
```bash
mysql -u root -p < database/setup.sql
```

2. Configure `.env` file:
```bash
cp .env.example .env
# Edit .env with your database credentials
```

## Running Tests

### Run All Tests

```bash
# Run all property tests
php tests/run_all_tests.php
```

### Run Test Suites

```bash
# Run all activity management tests
php tests/run_activity_tests.php
```

### Run Individual Property Tests

#### Activity Management Tests

```bash
# Property 1: Activity creation persistence
php tests/Property_1_ActivityCreationPersistence_Test.php

# Property 2: Category and tag association integrity
php tests/Property_2_CategoryTagAssociationIntegrity_Test.php

# Property 3: Optional field defaults
php tests/Property_3_OptionalFieldDefaults_Test.php

# Property 4: Activity update preservation
php tests/Property_4_ActivityUpdatePreservation_Test.php

# Property 5: Activity deletion cascade
php tests/Property_5_ActivityDeletionCascade_Test.php
```

#### Category and Tag Management Tests

```bash
# Property 6: Entity creation uniqueness
php tests/Property_6_EntityCreationUniqueness_Test.php

# Property 7: Entity rename association preservation
php tests/Property_7_EntityRenameAssociationPreservation_Test.php

# Property 8: Entity deletion cascade
php tests/Property_8_EntityDeletionCascade_Test.php

# Property 9: Duplicate name rejection
php tests/Property_9_DuplicateNameRejection_Test.php
```

#### User Isolation Tests

```bash
# Property 10: User activity isolation
php tests/Property_10_UserActivityIsolation_Test.php
```

#### Authentication Tests

```bash
# Property 16: User registration with encryption
php tests/Property_16_UserRegistrationEncryption_Test.php

# Property 17: Authentication with valid credentials
php tests/Property_17_AuthenticationValidCredentials_Test.php

# Property 18: Authentication with invalid credentials
php tests/Property_18_AuthenticationInvalidCredentials_Test.php

# Property 19: Session termination on logout
php tests/Property_19_SessionTermination_Test.php
```

## Test Structure

Each property test:
- Runs 100 iterations with randomly generated data
- Tests a specific correctness property from the design document
- Validates requirements from the requirements document
- Cleans up test data after execution

## Property Tests

### Activity Management

#### Property 1: Activity Creation Persistence
**Validates:** Requirements 1.1

Tests that created activities are retrievable from the database with all specified properties intact.

#### Property 2: Category and Tag Association Integrity
**Validates:** Requirements 1.2, 1.3

Tests that category and tag associations are correctly stored and retrievable.

#### Property 3: Optional Field Defaults
**Validates:** Requirements 1.4

Tests that activities created without optional fields have appropriate default values.

#### Property 4: Activity Update Preservation
**Validates:** Requirements 2.1, 2.4

Tests that activity updates persist new values while maintaining identity and unmodified properties.

#### Property 5: Activity Deletion Cascade
**Validates:** Requirements 2.2

Tests that deleting an activity removes it and all associations without orphaned references.

### Category and Tag Management

#### Property 6: Entity Creation Uniqueness
**Validates:** Requirements 1.5, 3.1, 4.1

Tests that each created entity (category or tag) has a unique identifier within a user's scope.

#### Property 7: Entity Rename Association Preservation
**Validates:** Requirements 3.2, 4.2

Tests that renaming categories or tags maintains all existing activity associations.

#### Property 8: Entity Deletion Cascade
**Validates:** Requirements 3.3, 4.3

Tests that deleting categories or tags appropriately updates associated activities.

#### Property 9: Duplicate Name Rejection
**Validates:** Requirements 3.4, 4.4

Tests that attempting to create entities with duplicate names is rejected.

### User Isolation

#### Property 10: User Activity Isolation
**Validates:** Requirements 5.1, 8.4

Tests that users can only access their own data, never other users' data.

### Authentication

#### Property 16: User Registration with Encryption
**Validates:** Requirements 8.1

Tests that passwords are stored as bcrypt hashes, never as plaintext.

#### Property 17: Authentication Success with Valid Credentials
**Validates:** Requirements 8.2

Tests that valid credentials authenticate users and create sessions.

#### Property 18: Authentication Failure with Invalid Credentials
**Validates:** Requirements 8.3

Tests that invalid credentials are rejected without creating sessions.

#### Property 19: Session Termination on Logout
**Validates:** Requirements 8.5

Tests that logout terminates sessions and requires re-authentication.

## Troubleshooting

### "Class mysqli not found"
- Enable the mysqli extension in php.ini
- Restart your web server

### "Connection failed"
- Check database credentials in .env file
- Ensure MySQL/MariaDB is running
- Verify database exists

### "Table doesn't exist"
- Run the database setup script: `mysql -u root -p < database/setup.sql`

## Test Output

Successful test output:
```
Running property test: Property 16: User registration with encryption
Iterations: 100
------------------------------------------------------------
------------------------------------------------------------
✓ PASSED: All 100 iterations passed
```

Failed test output:
```
Running property test: Property 16: User registration with encryption
Iterations: 100
------------------------------------------------------------
------------------------------------------------------------
✗ FAILED: 1 out of 100 iterations failed

First failure:
  Iteration: 42
  Data: {...}
  Exception: Password should be stored as bcrypt hash
```
