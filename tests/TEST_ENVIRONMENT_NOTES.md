# Test Environment Notes

## Current Status

All property-based tests have been implemented for the user authentication system:

✅ **Property 16**: User registration with encryption (Requirements 8.1)
✅ **Property 17**: Authentication success with valid credentials (Requirements 8.2)
✅ **Property 18**: Authentication failure with invalid credentials (Requirements 8.3)
✅ **Property 19**: Session termination on logout (Requirements 8.5)
✅ **Property 10**: User activity isolation (Requirements 5.1, 8.4)

## Running the Tests

### Prerequisites

Before running the tests, you need:

1. **PHP mysqli extension enabled**
   - Windows: Uncomment `extension=mysqli` in php.ini
   - Linux: `sudo apt-get install php-mysqli`
   - Mac: Usually included with PHP

2. **MySQL/MariaDB running**
   - Database must be created and accessible
   - Tables must be initialized (run `database/setup.sql`)

3. **Environment configured**
   - `.env` file must exist with valid database credentials
   - Copy from `.env.example` and update values

### Running Tests

```bash
# Run all authentication tests
php tests/run_all_tests.php

# Run individual tests
php tests/Property_16_UserRegistrationEncryption_Test.php
php tests/Property_17_AuthenticationValidCredentials_Test.php
php tests/Property_18_AuthenticationInvalidCredentials_Test.php
php tests/Property_19_SessionTermination_Test.php
php tests/Property_10_UserActivityIsolation_Test.php
```

## Test Implementation Details

### Property-Based Testing Framework

A custom lightweight property-based testing framework was implemented in `PropertyTestRunner.php`:

- Runs 100 iterations per property test
- Generates random test data for each iteration
- Provides clear pass/fail reporting
- Shows first failure details for debugging

### Test Helpers

`TestHelpers.php` provides utilities for:
- Generating random user data (usernames, emails, passwords)
- Cleaning up test data from database
- Checking password hash formats (bcrypt)
- Database query helpers

### Test Coverage

Each property test validates:

1. **Property 16 - Registration Encryption**
   - Passwords are never stored as plaintext
   - Passwords are stored as bcrypt hashes
   - Stored hashes can verify original passwords

2. **Property 17 - Valid Authentication**
   - Valid credentials authenticate successfully
   - Sessions are created and stored in database
   - User data is returned correctly
   - Sessions have valid expiration times

3. **Property 18 - Invalid Authentication**
   - Wrong passwords are rejected
   - Non-existent usernames are rejected
   - No sessions are created on failed login
   - Appropriate error codes are returned

4. **Property 19 - Session Termination**
   - Logout removes session from database
   - Terminated sessions cannot be verified
   - Subsequent logout attempts fail appropriately

5. **Property 10 - User Isolation**
   - Different users get different sessions
   - Sessions only return their own user data
   - Middleware correctly identifies users
   - No cross-user data access

## Known Issues

### mysqli Extension Not Available

If you see "Class mysqli not found":

1. Check if mysqli is installed:
   ```bash
   php -m | grep mysqli
   ```

2. Enable in php.ini:
   ```ini
   extension=mysqli
   ```

3. Restart web server:
   ```bash
   # Apache
   sudo systemctl restart apache2
   
   # Nginx with PHP-FPM
   sudo systemctl restart php8.0-fpm
   ```

### Database Connection Issues

If tests fail with connection errors:

1. Verify MySQL is running:
   ```bash
   sudo systemctl status mysql
   ```

2. Check .env credentials:
   ```bash
   cat .env | grep DB_
   ```

3. Test connection manually:
   ```bash
   mysql -u omnitrackr_user -p omnitrackr
   ```

### Table Not Found

If tests fail with "table doesn't exist":

```bash
mysql -u omnitrackr_user -p omnitrackr < database/setup.sql
```

## Next Steps

After setting up the test environment:

1. Run all tests to verify authentication system
2. Check that all 5 property tests pass
3. Review any failures and fix issues
4. Proceed to next task in implementation plan

## Test Maintenance

When modifying authentication code:

1. Run tests before making changes (baseline)
2. Make your changes
3. Run tests again to verify correctness
4. If tests fail, review the failure details
5. Fix code or update tests as needed

Remember: Property-based tests run 100 iterations with random data, so they may catch edge cases that unit tests miss!
