# 🚀 Wushu Student Portal - Production Deployment Guide

## 📋 Table of Contents
1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Server Requirements](#server-requirements)
3. [Installation Steps](#installation-steps)
4. [Security Configuration](#security-configuration)
5. [Database Setup](#database-setup)
6. [Testing Procedures](#testing-procedures)
7. [Maintenance & Monitoring](#maintenance--monitoring)
8. [Troubleshooting](#troubleshooting)

---

## ✅ Pre-Deployment Checklist

### Critical Security Items
- [ ] **Change default admin password immediately**
- [ ] **Update config.php with production database credentials**
- [ ] **Enable HTTPS/SSL certificate**
- [ ] **Set display_errors to Off in production**
- [ ] **Remove or protect password_fix.php**
- [ ] **Verify .htaccess security rules are active**
- [ ] **Set proper file permissions (644 for files, 755 for directories)**
- [ ] **Enable session security settings**
- [ ] **Configure automated database backups**
- [ ] **Test all payment workflows end-to-end**

### Configuration Files
- [ ] config.php - Database credentials updated
- [ ] .htaccess - Security rules enabled
- [ ] PHP settings - Error reporting configured
- [ ] File upload limits set (5MB minimum)

---

## 🖥️ Server Requirements

### Minimum Requirements
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7 or higher (MariaDB 10.3+ also works)
- **Apache**: 2.4 or higher (with mod_rewrite enabled)
- **Disk Space**: 500MB minimum (more for receipt storage)
- **Memory**: 128MB PHP memory limit minimum

### Required PHP Extensions
```bash
- PDO
- pdo_mysql
- mysqli
- mbstring
- openssl
- json
- fileinfo
- gd (for image processing)
```

### Verify PHP Extensions
```bash
php -m | grep -E 'pdo|mysqli|mbstring|openssl|json|fileinfo|gd'
```

---

## 📦 Installation Steps

### Step 1: Upload Files
```bash
# Upload all files to your web server
# Recommended structure:
/public_html/
  ├── index.php
  ├── admin.php
  ├── config.php
  ├── .htaccess
  ├── pages/
  ├── admin_pages/
  └── assets/ (if any)
```

### Step 2: Set File Permissions
```bash
# Set directory permissions
find /path/to/portal -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/portal -type f -exec chmod 644 {} \;

# Make sure config.php is not world-readable (optional extra security)
chmod 640 config.php
```

### Step 3: Configure Database
1. Create a new MySQL database:
   ```sql
   CREATE DATABASE mlxysf_student_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Create a database user:
   ```sql
   CREATE USER 'portal_user'@'localhost' IDENTIFIED BY 'strong_password_here';
   GRANT ALL PRIVILEGES ON mlxysf_student_portal.* TO 'portal_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. Import the schema:
   ```bash
   mysql -u portal_user -p mlxysf_student_portal < database_schema.sql
   ```

### Step 4: Update config.php
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mlxysf_student_portal');
define('DB_USER', 'portal_user');
define('DB_PASS', 'your_strong_password');

// Site Configuration
define('SITE_NAME', 'Your Wushu Academy Name');
define('SITE_URL', 'https://yourdomain.com');

// Security
ini_set('display_errors', 0);  // CRITICAL: Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/logs/php-error.log');
```

### Step 5: Change Default Admin Password
```bash
# Method 1: Use password_fix.php (then delete it)
# Access: https://yourdomain.com/password_fix.php
# Set new password, then DELETE the file

# Method 2: Manual SQL update
php -r "echo password_hash('new_secure_password', PASSWORD_DEFAULT);"
# Copy the hash and run:
# UPDATE admin_users SET password='$2y$10$...' WHERE username='admin';
```

---

## 🔒 Security Configuration

### 1. HTTPS/SSL Setup
```apache
# In .htaccess, uncomment these lines after SSL is configured:
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Session Security
Add to config.php:
```php
// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // Only if HTTPS is enabled
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
```

### 3. File Upload Security
Verify in .htaccess or php.ini:
```ini
upload_max_filesize = 5M
post_max_size = 6M
file_uploads = On
```

### 4. Database Security
- Use strong passwords (minimum 16 characters)
- Restrict database user to only necessary privileges
- Use prepared statements (already implemented in code)
- Regularly update MySQL/MariaDB

### 5. Remove Development Files
```bash
# Delete these files in production:
rm password_fix.php
rm database_schema.sql  # After import
rm README.md  # Optional, but contains setup info
```

---

## 💾 Database Setup

### Import Complete Schema
```bash
mysql -u username -p database_name < database_schema.sql
```

### Verify Tables Were Created
```sql
USE mlxysf_student_portal;
SHOW TABLES;

-- Should show:
-- admin_users
-- students
-- classes
-- enrollments
-- invoices
-- payments
-- attendance
```

### Create First Admin (if not exists)
```sql
INSERT INTO admin_users (username, password, full_name, email, role)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System Administrator',
    'admin@yourdomain.com',
    'super_admin'
);
-- Default password: admin123 (CHANGE IMMEDIATELY!)
```

### Set Up Automated Backups
```bash
# Create backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/student_portal"
mkdir -p $BACKUP_DIR

mysqldump -u username -ppassword mlxysf_student_portal > $BACKUP_DIR/backup_$DATE.sql

# Keep only last 30 days
find $BACKUP_DIR -name "backup_*.sql" -mtime +30 -delete

# Add to crontab: Run daily at 2 AM
# 0 2 * * * /path/to/backup_script.sh
```

---

## 🧪 Testing Procedures

### 1. Admin Portal Testing
- [ ] Login with admin credentials
- [ ] Create a test student
- [ ] Create a test class
- [ ] Enroll student in class
- [ ] Create an invoice for student
- [ ] Mark attendance
- [ ] Verify all CRUD operations work

### 2. Student Portal Testing
- [ ] Login as test student
- [ ] View dashboard (shows classes and stats)
- [ ] View invoices page
- [ ] Upload a payment receipt
- [ ] Verify payment appears as "pending"
- [ ] Check invoice status changes to "pending"
- [ ] View attendance records
- [ ] Update profile information

### 3. Payment Workflow Testing
**Critical: This is the most important flow**

#### Test Case 1: Invoice Payment
1. Admin creates invoice → status = "unpaid"
2. Student views invoice in portal
3. Student clicks "Pay" and uploads receipt
4. Invoice status becomes "pending" (NOT paid!)
5. Payment record created with "pending" verification
6. Admin verifies payment in admin portal
7. Invoice status becomes "paid"
8. Paid date is set

#### Test Case 2: Direct Payment (No Invoice)
1. Student uploads payment without invoice
2. Payment shows as "pending" in admin
3. Admin verifies payment
4. Payment shows as "verified"

### 4. Security Testing
- [ ] SQL injection protection (test with ' OR '1'='1)
- [ ] XSS protection (test with <script>alert(1)</script>)
- [ ] Session security (logout, try accessing pages)
- [ ] File upload validation (try uploading .php file)
- [ ] Direct file access (try accessing config.php directly)
- [ ] Password requirements enforced

---

## 🔧 Maintenance & Monitoring

### Daily Tasks
- [ ] Monitor error logs
- [ ] Check pending payment verifications
- [ ] Verify automated backups ran

### Weekly Tasks
- [ ] Review failed login attempts
- [ ] Check database size/performance
- [ ] Update overdue invoice statuses

### Monthly Tasks
- [ ] Security audit
- [ ] Update PHP/MySQL if needed
- [ ] Review and archive old data
- [ ] Test backup restoration

### Update Overdue Invoices (Cron Job)
```sql
-- Create a cron job to run this daily:
UPDATE invoices 
SET status = 'overdue' 
WHERE status = 'unpaid' 
AND due_date < CURDATE();
```

### Monitor Database Size
```sql
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'mlxysf_student_portal'
ORDER BY (data_length + index_length) DESC;
```

---

## 🐛 Troubleshooting

### Common Issues

#### Issue: "500 Internal Server Error"
**Solutions:**
1. Check PHP error logs: `tail -f /var/log/php_errors.log`
2. Verify .htaccess syntax
3. Check file permissions (644/755)
4. Ensure PHP extensions are loaded

#### Issue: "Database connection failed"
**Solutions:**
1. Verify config.php credentials
2. Check if MySQL service is running: `systemctl status mysql`
3. Test connection: `mysql -u username -p database_name`
4. Verify user has correct privileges

#### Issue: "Unable to upload receipt"
**Solutions:**
1. Check upload_max_filesize in php.ini
2. Verify post_max_size > upload_max_filesize
3. Check directory permissions
4. Look for PHP errors in logs

#### Issue: "Invoice status not updating after payment verification"
**Solutions:**
1. Verify payments table has `invoice_id` column
2. Check admin.php verify_payment logic
3. Check for JavaScript errors in browser console
4. Verify foreign key constraints

#### Issue: "Session expires too quickly"
**Solutions:**
```php
// Add to config.php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_lifetime', 3600);
```

### Enable Debug Mode (Development Only)
```php
// Temporarily add to config.php for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// REMOVE BEFORE GOING TO PRODUCTION!
```

### Check PHP Configuration
```bash
php -i | grep -E 'upload_max_filesize|post_max_size|memory_limit'
```

---

## 📞 Support & Resources

### Log Files to Check
- PHP errors: `/var/log/php_errors.log`
- Apache errors: `/var/log/apache2/error.log`
- MySQL errors: `/var/log/mysql/error.log`

### Useful SQL Queries

```sql
-- View all pending payments
SELECT p.*, s.full_name, c.class_code 
FROM payments p 
JOIN students s ON p.student_id = s.id 
JOIN classes c ON p.class_id = c.id 
WHERE p.verification_status = 'pending';

-- View unpaid invoices
SELECT i.*, s.full_name, s.email 
FROM invoices i 
JOIN students s ON i.student_id = s.id 
WHERE i.status = 'unpaid' 
ORDER BY i.due_date;

-- View student payment history
SELECT * FROM payments 
WHERE student_id = (SELECT id FROM students WHERE student_id = 'STU00001')
ORDER BY upload_date DESC;
```

---

## ✨ Post-Deployment Checklist

- [ ] All security items completed
- [ ] Default passwords changed
- [ ] HTTPS enabled and working
- [ ] Database backups configured
- [ ] Error logging configured
- [ ] Test student and admin accounts created
- [ ] All payment workflows tested end-to-end
- [ ] Email notifications configured (if applicable)
- [ ] Mobile responsiveness verified
- [ ] Browser compatibility tested (Chrome, Firefox, Safari)
- [ ] Load testing performed (if high traffic expected)
- [ ] Documentation updated with site-specific info

---

## 🎉 Success!

Your Wushu Student Portal is now ready for production use!

### Next Steps:
1. Train admin users on the system
2. Import existing student data (if any)
3. Set up class schedules
4. Generate invoices for the current month
5. Notify students about the new portal

### Regular Maintenance:
- Daily: Check pending payments and logs
- Weekly: Generate reports, review system health
- Monthly: Security audit, backup verification
- Quarterly: Update software, review performance

---

**Last Updated:** December 2025  
**Version:** 1.0  
**Tested On:** PHP 8.0+, MySQL 8.0+, Apache 2.4+