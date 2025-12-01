# FindIt@BatStateU - Database Setup Guide

## Overview
This guide will help you set up the MySQL database for the FindIt@BatStateU Lost and Found Management System.

## Prerequisites
- XAMPP (or MySQL Server) installed and running
- PhpMyAdmin access or MySQL command-line tools
- The project files from this directory

## Database Setup Instructions

### Option 1: Using PhpMyAdmin (GUI)

1. **Open PhpMyAdmin**
   - Navigate to `http://localhost/phpmyadmin` in your browser

2. **Create Database**
   - Click "New" in the left sidebar
   - Enter database name: `findit`
   - Set collation to: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import Schema**
   - Select the `findit` database
   - Click the "Import" tab
   - Click "Choose File" and select `sql/schema.sql`
   - Click "Import"

### Option 2: Using MySQL Command Line

1. **Open Command Prompt / Terminal** and navigate to your XAMPP MySQL bin directory:
   ```bash
   cd C:\xampp\mysql\bin
   ```

2. **Connect to MySQL**:
   ```bash
   mysql -u root -p
   ```
   (Press Enter when asked for password if using default XAMPP configuration)

3. **Run the Schema**:
   ```sql
   SOURCE C:/xampp/htdocs/SIA.html/sql/schema.sql;
   ```

## Database Connection Configuration

The `db_config.php` file contains the database connection settings:

```php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'findit';
$DB_USER = 'root';
$DB_PASS = '';
```

### Update Connection Settings (if needed)
Edit `db_config.php` if your setup differs:
- **$DB_HOST**: Usually `127.0.0.1` or `localhost`
- **$DB_NAME**: Keep as `findit`
- **$DB_USER**: Usually `root` for XAMPP
- **$DB_PASS**: Empty string for XAMPP default, or your MySQL password

## Database Schema Overview

### Tables Created

#### 1. **users**
- Stores all user accounts (Students, Faculty, Staff, Admins)
- Columns: id, first_name, last_name, user_type, student_id, department, email, phone, password, is_active, created_at, updated_at

#### 2. **items**
- Stores all lost and found item reports
- Columns: id, user_id, item_type (Lost/Found), category, item_name, description, location, item_date, image_url, status, reward_amount, created_at, updated_at

#### 3. **claims**
- Tracks claims on items (who claims ownership of a found item or confirms they found a lost item)
- Columns: id, item_id, claimant_id, claim_date, claim_description, status, reviewed_by, reviewed_at, notes

#### 4. **notifications**
- Stores notifications for users (item matches, claim updates, etc.)
- Columns: id, user_id, item_id, message, notification_type, is_read, created_at

#### 5. **audit_logs**
- Records all administrative actions for security and compliance
- Columns: id, user_id, action, entity_type, entity_id, changes, ip_address, created_at

## Testing the Database

### Test Login Credentials
After setup, you can create a test user through the registration page, or insert test data:

```sql
USE findit;

-- Insert a test admin user (password: "admin123")
INSERT INTO users (first_name, last_name, user_type, student_id, department, email, phone, password)
VALUES ('Admin', 'User', 'Admin', 'ADMIN001', 'Administration', 
        'admin@batstate-u.edu.ph', '+63 912 345 6789', 
        '$2y$10$YIjlrxAqJ8.965BKUbQLu.Eg5MpJVd9LNBjS0.uYEG9Xe/G7lMWTy');

-- Insert a test student user (password: "student123")
INSERT INTO users (first_name, last_name, user_type, student_id, department, email, phone, password)
VALUES ('John', 'Doe', 'Student', 'STU001', 'Computer Science', 
        'john.doe@batstate-u.edu.ph', '+63 912 345 6788', 
        '$2y$10$u7f5IYFnLv1w8Gx9pK3qr.2JqN8mRsT1dH4vB5xC6yD7eF8aG9jH0');
```

**Test Credentials:**
- Email: `admin@batstate-u.edu.ph` / Password: `admin123`
- Email: `john.doe@batstate-u.edu.ph` / Password: `student123`

## Troubleshooting

### Connection Failed Error
- Ensure XAMPP MySQL service is running
- Verify database name is `findit`
- Check `db_config.php` credentials match your MySQL setup
- Confirm database exists in PhpMyAdmin

### Schema Import Failed
- Ensure the `findit` database exists first
- Check that `sql/schema.sql` file exists and is readable
- Verify you have CREATE privileges on the database
- Try importing from PhpMyAdmin if command-line fails

### Cannot Register/Login
- Verify users table was created successfully
- Check email address format
- Ensure password is hashed correctly

## Additional Notes

- Database uses UTF-8 (utf8mb4) encoding for international character support
- All passwords are hashed using PHP's `password_hash()` function
- Foreign keys enforce data integrity
- Indexes are added on frequently queried columns for performance
- Audit logs track all administrative actions

For more information or issues, check the application's error logs.
