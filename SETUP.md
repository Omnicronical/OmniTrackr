# OmniTrackr Setup Guide

## Prerequisites

### Required Software

1. **PHP 8.0 or higher**
   - Download from: https://www.php.net/downloads
   - Ensure the following extensions are enabled:
     - mysqli
     - pdo_mysql
     - json
     - bcrypt (included in PHP core)

2. **MySQL 8.0+ or MariaDB 10.5+**
   - MySQL: https://dev.mysql.com/downloads/
   - MariaDB: https://mariadb.org/download/

3. **Web Server**
   - Apache 2.4+ (recommended) or
   - Nginx 1.18+

## Installation Steps

### 1. Clone or Download the Project

```bash
cd /var/www/
git clone <repository-url> omnitrackr
cd omnitrackr
```

### 2. Configure PHP

#### Enable Required Extensions

**Windows:**
1. Locate your `php.ini` file (run `php --ini` to find it)
2. Open `php.ini` in a text editor
3. Find and uncomment these lines (remove the semicolon):
   ```ini
   extension=mysqli
   extension=pdo_mysql
   ```
4. Save and restart your web server

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get update
sudo apt-get install php php-mysqli php-pdo-mysql php-json
sudo systemctl restart apache2
```

**Mac (with Homebrew):**
```bash
brew install php
# Extensions are usually included by default
brew services restart php
```

### 3. Set Up Database

#### Create Database and User

```bash
# Login to MySQL
mysql -u root -p

# Run the setup script
mysql -u root -p < database/setup.sql
```

Or manually:

```sql
-- Create database
CREATE DATABASE omnitrackr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (change password!)
CREATE USER 'omnitrackr_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON omnitrackr.* TO 'omnitrackr_user'@'localhost';
FLUSH PRIVILEGES;
```

Then run the table creation:
```bash
mysql -u omnitrackr_user -p omnitrackr < database/setup.sql
```

### 4. Configure Environment

```bash
# Copy the example environment file
cp .env.example .env

# Edit the .env file with your settings
nano .env
```

Update these values in `.env`:
```ini
DB_HOST=localhost
DB_NAME=omnitrackr
DB_USER=omnitrackr_user
DB_PASSWORD=your_secure_password
DB_PORT=3306

SESSION_LIFETIME=86400
SESSION_NAME=omnitrackr_session

APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

# Generate a random string for session security
SESSION_SECRET=your_random_secret_key_here
```

### 5. Configure Web Server

#### Apache Configuration

Create a virtual host file: `/etc/apache2/sites-available/omnitrackr.conf`

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/omnitrackr/public

    <Directory /var/www/omnitrackr/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/omnitrackr-error.log
    CustomLog ${APACHE_LOG_DIR}/omnitrackr-access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite omnitrackr
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx Configuration

Create a server block: `/etc/nginx/sites-available/omnitrackr`

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/omnitrackr/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/omnitrackr /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

### 6. Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/omnitrackr

# Set permissions
sudo chmod -R 755 /var/www/omnitrackr
sudo chmod -R 775 /var/www/omnitrackr/public
```

### 7. Test the Installation

#### Test Database Connection

```bash
php -r "require 'src/config/database.php'; \$db = new Database(); \$conn = \$db->getConnection(); echo 'Database connection successful!';"
```

#### Test API Endpoints

```bash
# Test registration
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","email":"test@example.com","password":"testpass123"}'

# Test login
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"testpass123"}'
```

### 8. Run Property-Based Tests

```bash
# Run all tests
php tests/run_all_tests.php

# Or run individual tests
php tests/Property_16_UserRegistrationEncryption_Test.php
php tests/Property_17_AuthenticationValidCredentials_Test.php
php tests/Property_18_AuthenticationInvalidCredentials_Test.php
php tests/Property_19_SessionTermination_Test.php
php tests/Property_10_UserActivityIsolation_Test.php
```

## Security Considerations

### Production Deployment

1. **Enable HTTPS**
   ```bash
   # Install Certbot for Let's Encrypt
   sudo apt-get install certbot python3-certbot-apache
   sudo certbot --apache -d your-domain.com
   ```

2. **Update .env for Production**
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Secure Session Cookies**
   - Update `public/api/auth/login.php` to set `secure` flag to `true`
   - This requires HTTPS

4. **Database Security**
   - Use strong passwords
   - Limit database user privileges
   - Enable MySQL firewall rules

5. **File Permissions**
   - Ensure `.env` is not publicly accessible
   - Set restrictive permissions: `chmod 600 .env`

## Troubleshooting

### "Class mysqli not found"
- Enable mysqli extension in php.ini
- Restart web server

### "Connection refused"
- Check if MySQL is running: `sudo systemctl status mysql`
- Verify database credentials in .env

### "Table doesn't exist"
- Run database setup: `mysql -u root -p < database/setup.sql`

### "Permission denied"
- Check file ownership: `ls -la /var/www/omnitrackr`
- Fix permissions: `sudo chown -R www-data:www-data /var/www/omnitrackr`

### API Returns 404
- Check .htaccess file exists in public/
- Enable mod_rewrite: `sudo a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`

## Next Steps

After successful installation:

1. Access the application at `http://your-domain.com`
2. Create your first user account
3. Start tracking activities!

For development:
- Review the design document: `.kiro/specs/omnitrackr/design.md`
- Check the task list: `.kiro/specs/omnitrackr/tasks.md`
- Read the requirements: `.kiro/specs/omnitrackr/requirements.md`
