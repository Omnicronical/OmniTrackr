# OmniTrackr

A highly modular activity tracking application that enables users to track diverse activities such as certifications, project roles, tickets, and events.

## Features

- **User Authentication**: Secure registration, login, and session management with bcrypt password hashing
- **Activity Tracking**: Customizable activity tracking with categories and tags
- **Filtering & Search**: Advanced filtering capabilities by category and tags
- **Analytics Dashboard**: Statistics and visualizations of your activities
- **Modern UI**: Accessible interface with smooth animations
- **Elegant Design**: White, grey, and gold color scheme with rounded corners
- **Data Isolation**: Each user's data is completely isolated and secure

## Requirements

- PHP 8.0 or higher
- MySQL 8.0+ or MariaDB 10.5+
- Apache 2.4+ or Nginx 1.18+
- Web server with mod_rewrite enabled (for Apache)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd omnitrackr
```

### 2. Configure Environment

Copy the example environment file and update with your settings:

```bash
cp .env.example .env
```

Edit `.env` and update the following values:
- `DB_HOST`: Your database host (default: localhost)
- `DB_NAME`: Database name (default: omnitrackr)
- `DB_USER`: Database username
- `DB_PASSWORD`: Your secure database password
- `SESSION_SECRET`: Generate a random string for session encryption

### 3. Set Up Database

Run the database setup script:

```bash
mysql -u root -p < database/setup.sql
```

Or create the database user manually:

```sql
CREATE DATABASE omnitrackr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'omnitrackr_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON omnitrackr.* TO 'omnitrackr_user'@'localhost';
FLUSH PRIVILEGES;
```

Then run the setup script:

```bash
mysql -u omnitrackr_user -p omnitrackr < database/setup.sql
```

### 4. Configure Web Server

#### Apache

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName omnitrackr.local
    DocumentRoot /path/to/omnitrackr/public
    
    <Directory /path/to/omnitrackr/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/omnitrackr-error.log
    CustomLog ${APACHE_LOG_DIR}/omnitrackr-access.log combined
</VirtualHost>
```

#### Nginx

Create a server block configuration:

```nginx
server {
    listen 80;
    server_name omnitrackr.local;
    root /path/to/omnitrackr/public;
    
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

### 5. Set File Permissions

```bash
chmod -R 755 /path/to/omnitrackr
chmod -R 775 /path/to/omnitrackr/database
```

### 6. Test Installation

Visit your configured URL (e.g., http://omnitrackr.local) to verify the installation.

## Project Structure

```
omnitrackr/
├── public/              # Web root
│   ├── index.php       # Application entry point
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── assets/         # Images and other assets
├── src/                # PHP source code
│   ├── controllers/    # API controllers
│   ├── models/         # Data models
│   ├── middleware/     # Authentication and validation
│   └── config/         # Configuration files
├── database/           # Database scripts
│   └── setup.sql       # Database setup script
├── .env.example        # Environment configuration template
└── README.md           # This file
```

## API Endpoints

### Authentication

- `POST /api/auth/register` - Register a new user
  ```json
  {
    "username": "johndoe",
    "email": "john@example.com",
    "password": "securepassword"
  }
  ```

- `POST /api/auth/login` - Login and create session
  ```json
  {
    "username": "johndoe",
    "password": "securepassword"
  }
  ```

- `POST /api/auth/logout` - Logout and terminate session
  - Requires session cookie or Authorization header

## Development

### Running Locally

1. Ensure your web server is running
2. Ensure MySQL/MariaDB is running
3. Access the application through your configured URL

### Running Tests

Property-based tests are included to verify correctness:

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

See `tests/README.md` for detailed testing documentation.

### Database Management

To reset the database:

```bash
mysql -u omnitrackr_user -p omnitrackr < database/setup.sql
```

## Security Notes

- Never commit your `.env` file to version control
- Use strong passwords for database users
- Generate a secure random string for `SESSION_SECRET`
- Enable HTTPS in production environments
- Keep PHP and database software updated

## License

[Your License Here]

## Support

For issues and questions, please open an issue on the repository.
