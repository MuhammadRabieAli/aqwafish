# AquaBase - Fish Information System

AquaBase is a PHP-based web application for managing and sharing fish information. It allows users to submit information about fish species, which admins can review and approve for public display.

## Features

- **User Authentication**: Register, login, and manage user accounts
- **Fish Submission System**: Users can submit fish information with multiple images
- **Admin Dashboard**: Admins can review, approve, or reject submissions
- **User Management**: Admins can manage user accounts
- **Public Fish Listing**: Public page to view approved fish with search and filtering options
- **Responsive Design**: Mobile-friendly interface

## Installation

1. **Set up the database**

   - Create a MySQL database
   - Update database connection details in `config.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'aqwa');
     ```

2. **Set up the application**

   - Upload the files to your web server
   - Make sure the `uploads` directory is writable:
     ```
     chmod 755 uploads
     ```

3. **Run the setup script**

   - Navigate to `http://yourdomain.com/setup_db.php` to create the necessary tables
   - This will also create a default admin user (email: admin@aquabase.com, password: admin123)
   - **Important**: Delete or rename setup_db.php after setup for security

4. **Access the application**

   - Visit `http://yourdomain.com/` to access the public interface
   - Visit `http://yourdomain.com/login.php` to log in as admin

## Directory Structure

- `/` - Root directory with main PHP files
- `/admin` - Admin dashboard and management files
- `/user` - User dashboard files
- `/includes` - Common includes (header, footer, etc.)
- `/styles` - CSS files
- `/scripts` - JavaScript files
- `/uploads` - Directory for storing uploaded images

## User Roles

- **Regular Users**:

  - Submit fish information
  - View their submissions and status
  - Edit pending submissions

- **Administrators**:
  - Approve or reject fish submissions
  - Manage users (add, edit, delete)
  - Delete fish entries
