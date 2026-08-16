# 🚀 DEPLOYMENT GUIDE - Quick Start

## **Pre-Deployment Checklist**

### ✅ Server Requirements
- [ ] PHP 7.4 or higher
- [ ] MySQL 5.7+ or MariaDB 10.3+
- [ ] Apache with `.htaccess` support (or configure nginx equivalent)
- [ ] 50MB+ disk space
- [ ] Write permissions for `uploads/` and `logs/` directories

### ✅ Environment Setup
- [ ] Web server installed (XAMPP, WAMP, LAMP, or standalone)
- [ ] MySQL/MariaDB running
- [ ] phpMyAdmin or MySQL CLI available

---

## **🔧 STEP-BY-STEP DEPLOYMENT**

### **STEP 1: Download/Clone Repository**

**Option A: Clone from GitHub**
```bash
git clone https://github.com/Shubh0o7/complaint-management-system.git
cd complaint-management-system
git checkout master
```

**Option B: Download ZIP**
- Visit: https://github.com/Shubh0o7/complaint-management-system
- Click "Code" → "Download ZIP"
- Extract to your web server directory

---

### **STEP 2: Place in Web Root**

#### **For XAMPP (Windows/Mac/Linux)**
```bash
cp -r complaint-management-system /path/to/xampp/htdocs/
# Windows: C:\xampp\htdocs\
# Mac: /Applications/XAMPP/htdocs/
# Linux: /opt/lampp/htdocs/
```

#### **For WAMP (Windows)**
```bash
# Copy to: C:\wamp64\www\complaint-management-system\
```

#### **For Linux/Apache**
```bash
sudo cp -r complaint-management-system /var/www/html/
sudo chown -R www-data:www-data complaint-management-system
sudo chmod -R 755 complaint-management-system
```

#### **For Nginx + PHP-FPM**
```bash
sudo cp -r complaint-management-system /var/www/html/
sudo chown -R nginx:nginx complaint-management-system
sudo chmod -R 755 complaint-management-system
# Add nginx config for rewrite rules (if needed)
```

---

### **STEP 3: Set Directory Permissions**

```bash
# Make uploads directory writable
chmod 755 uploads/

# Create logs directory
mkdir -p logs/
chmod 755 logs/

# Verify permissions
ls -ld uploads/ logs/
```

---

### **STEP 4: Create Database**

#### **Option A: Using phpMyAdmin (Easiest)**

1. Open: `http://localhost/phpmyadmin`
2. Login with default credentials (usually no password)
3. Click "New" (or "Create database")
4. Database name: `complaint_system`
5. Collation: `utf8mb4_unicode_ci`
6. Click "Create"
7. Select the new database
8. Click "Import" tab
9. Choose `database.sql` from the project root
10. Click "Import"
11. You should see: ✅ "Import has been successfully finished"

#### **Option B: Using MySQL CLI**

```bash
mysql -u root -p << EOF
CREATE DATABASE complaint_system 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;
USE complaint_system;
SOURCE database.sql;
SHOW TABLES;
EOF
```

#### **Option C: Using Command Line**

```bash
# Navigate to project directory
cd complaint-management-system

# Import database
mysql -u root -p complaint_system < database.sql
```

#### **Verify Import**
```bash
# Login to MySQL and check
mysql -u root -p complaint_system -e "SHOW TABLES;"
```

Expected output:
```
+-----------------------------+
| Tables_in_complaint_system  |
+-----------------------------+
| categories                  |
| complaint_attachments       |
| complaint_comments          |
| complaint_timeline          |
| complaints                  |
| notifications               |
| users                       |
+-----------------------------+
```

---

### **STEP 5: Configure Database Connection**

Edit `config.php`:

```php
<?php
define('DB_HOST', 'localhost');    // Usually correct
define('DB_USER', 'root');         // Default MySQL user
define('DB_PASS', '');             // Default: empty (change if you have password)
define('DB_NAME', 'complaint_system');

// Test connection (uncomment to test)
// $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// if ($conn->connect_error) {
//     die('Connection failed: ' . $conn->connect_error);
// }
// echo "Connected successfully!";
?>
```

**Common configurations:**
```php
// XAMPP default
define('DB_USER', 'root');
define('DB_PASS', '');

// WAMP default
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Production
define('DB_USER', 'cms_user');
define('DB_PASS', 'secure_password_here');
```

---

### **STEP 6: Start Services**

#### **XAMPP**
```bash
# Windows: Start XAMPP Control Panel, click "Start" for Apache & MySQL
# Mac/Linux: 
sudo /Applications/XAMPP/xamppfiles/xampp start  # Mac
sudo /opt/lampp/lampp start                      # Linux
```

#### **WAMP**
- Open WAMP system tray icon
- Click "Start All Services"

#### **Linux/Apache**
```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

#### **Verify Services Running**
```bash
# Check Apache
curl http://localhost/

# Check MySQL
mysql -u root -p -e "SELECT 1;"
```

---

### **STEP 7: Test Application**

#### **Access the App**
- **Main Site:** http://localhost/complaint-management-system/
- **Admin Panel:** http://localhost/complaint-management-system/admin_dashboard.php

#### **Login with Default Account**
```
Email: admin@cms.com
Password: admin123
```

✅ You should see the admin dashboard with stats

---

### **STEP 8: First-Time Setup**

#### **Change Default Admin Password**
1. Login as `admin@cms.com` / `admin123`
2. (Feature not yet implemented - manually update database for now):
   ```sql
   UPDATE users SET password = PASSWORD('your_new_password') 
   WHERE email = 'admin@cms.com';
   ```

#### **Create Test User**
1. Logout from admin account
2. Click "Register here"
3. Fill registration form:
   - Full Name: Test User
   - Email: test@example.com
   - Password: Test@123
   - Confirm: Test@123
4. Click "Register"
5. Login with new credentials

#### **Test Features**
1. Go to "New Complaint" (in sidebar)
2. Fill form:
   - Subject: Test complaint
   - Category: IT Support
   - Priority: Medium
   - Description: This is a test complaint
3. Click "Submit"
4. Go to "My Complaints" to see it listed
5. Click complaint to view details
6. Test adding a comment
7. Login as admin to update status

---

## **🔍 TROUBLESHOOTING**

### **Issue: Connection Failed**
```
Error: Connection failed: Access denied for user 'root'@'localhost'
```

**Solutions:**
1. Check MySQL is running: `mysql -u root -p -e "SELECT 1;"`
2. Verify credentials in `config.php`
3. Check MySQL user exists: `mysql -u root -p -e "SELECT user FROM mysql.user;"`
4. Reset MySQL password:
   ```bash
   # Stop MySQL
   sudo systemctl stop mysql
   # Start without password check
   sudo mysqld --skip-grant-tables
   # In another terminal
   mysql -u root
   # Flush privileges and set password
   FLUSH PRIVILEGES;
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'newpassword';
   ```

---

### **Issue: 404 Not Found**
```
The requested URL /complaint-management-system/ was not found on this server
```

**Solutions:**
1. Verify file location:
   - XAMPP: `C:\xampp\htdocs\complaint-management-system\`
   - WAMP: `C:\wamp64\www\complaint-management-system\`
   - Linux: `/var/www/html/complaint-management-system/`

2. Check Apache mod_rewrite is enabled:
   ```bash
   # Linux
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. Test Apache:
   ```bash
   curl http://localhost/
   # Should return HTML
   ```

---

### **Issue: Permission Denied on uploads/**
```
Warning: Failed to open stream: Permission denied in api/upload_attachment.php
```

**Solutions:**
```bash
# Fix permissions
chmod 777 uploads/
chmod 777 logs/

# Or more securely (for production)
sudo chown www-data:www-data uploads/
sudo chmod 755 uploads/
```

---

### **Issue: Blank Screen / Internal Error**
```
PHP Fatal error or blank page
```

**Solutions:**
1. Enable error logging in `config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. Check PHP error log:
   ```bash
   # XAMPP
   tail -f /path/to/xampp/logs/php_error.log
   
   # Linux
   tail -f /var/log/php-fpm.log
   ```

3. Verify PHP version:
   ```bash
   php -v  # Should be 7.4+
   ```

---

### **Issue: Database Import Failed**
```
SQL syntax error or import hangs
```

**Solutions:**
1. Check file encoding (should be UTF-8):
   ```bash
   file database.sql
   ```

2. Try importing with verbose output:
   ```bash
   mysql -u root -p complaint_system < database.sql -v
   ```

3. Check database size limit (phpMyAdmin max upload):
   - Edit `php.ini`: `upload_max_filesize = 100M`
   - Edit `php.ini`: `post_max_size = 100M`

4. Split import if too large:
   ```bash
   # Import table by table
   mysql -u root -p complaint_system < tables.sql
   ```

---

## **📊 POST-DEPLOYMENT CHECKS**

Run these commands to verify everything works:

```bash
# 1. Check all required files exist
ls -la uploads/
ls -la config.php
ls -la api/
ls -la includes/

# 2. Test database connection
php -r "require 'config.php'; echo 'DB Connected: ' . ($conn ? 'YES' : 'NO');"

# 3. Check PHP version
php -v

# 4. List database tables
mysql -u root -p complaint_system -e "SHOW TABLES;"

# 5. Count users
mysql -u root -p complaint_system -e "SELECT COUNT(*) FROM users;"

# 6. Check file permissions
stat uploads/
stat logs/
```

---

## **🎯 PRODUCTION DEPLOYMENT**

### **Security Hardening**
```bash
# 1. Disable directory listing
chmod 644 .htaccess
echo "Options -Indexes" > .htaccess

# 2. Set secure permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 777 uploads/ logs/

# 3. Protect sensitive files
chmod 600 config.php

# 4. Enable HTTPS
# Use Let's Encrypt (free SSL)
```

### **Performance Optimization**
```bash
# 1. Enable PHP OPcache
php -i | grep opcache

# 2. Enable MySQL query caching
# In my.cnf: query_cache_size=256M

# 3. Enable gzip compression
# In .htaccess or nginx.conf
```

### **Monitoring**
```bash
# 1. Check disk space
df -h

# 2. Monitor MySQL
mysqladmin processlist

# 3. Check logs
tail -f /var/log/apache2/error.log
tail -f /var/log/mysql/error.log
```

---

## **✅ DEPLOYMENT COMPLETE!**

Your Complaint Management System is now ready to use!

**Next Steps:**
1. ✅ Login as admin (admin@cms.com / admin123)
2. ✅ Create test complaints
3. ✅ Test admin features
4. ✅ Register new users
5. ✅ Customize system as needed

**Support:**
- Check README.md for detailed documentation
- Review code comments in API files
- Check database schema in database.sql

**Happy Complaining! 🎉**
