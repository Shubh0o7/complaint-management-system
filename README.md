# Complaint Management System

A full-featured web-based complaint management system built with PHP, MySQL, and Bootstrap 5. Features a clean blue & white theme, role-based access control, comprehensive admin panel, complaint tracking timeline, file attachments, comment system, notifications, and analytics dashboard.

## Features

### Phase 1 — Core System
- **User Authentication** — Register, Login, Logout with `password_hash()`/`password_verify()` and prepared statements
- **Dashboard** — Welcome banner, profile card, complaint statistics by status
- **Submit Complaints** — Form with subject, category, priority, and description
- **View Complaints** — User-scoped complaint listing with search & filter
- **Session Management** — Auth guards on all protected pages

### Phase 2 — Admin Panel & Enhanced Features
- **Admin Dashboard** — Overview stats (total users, complaints, pending, critical), recent complaints table
- **Admin Complaint Management** — View all complaints, update status, filter by status/category/priority, search
- **Admin User Management** — View all registered users, see complaint counts per user, promote/demote admin role
- **Complaint Categories** — Database-driven categories (IT Support, Infrastructure, Academic, Administrative, Hostel, Transport, Ragging, Other)
- **Priority Levels** — Low, Medium, High, Critical with color-coded badges
- **Status Tracking** — Pending, In Progress, Resolved, Rejected with admin-controlled updates
- **Search & Filter** — Filter complaints by status, category, priority; search by subject/description
- **Role-Based Access** — Admin vs User roles with separate dashboards and navigation
- **Dynamic Sidebar** — Shows admin links only for admin users

### Phase 3 — Advanced Features
- **Complaint Detail View** — Full complaint page with all metadata, description, and action panels
- **Complaint Tracking Timeline** — Visual timeline showing status changes, comments, and attachments with timestamps
- **File Attachments** — Upload files (images, PDFs, documents) when submitting complaints; view/download from detail page
- **Comment System** — Users and admins can add comments on complaints; threaded discussion with role indicators
- **Email Notifications** — Email alerts on status changes, new comments, and complaint updates (configurable)
- **In-App Notifications** — Real-time notification bell with unread count; notification center page with mark-as-read
- **Reports & Analytics** — Admin analytics dashboard with Chart.js: complaints by status, category, priority, monthly trends
- **Timeline Entries on Status Change** — Automatic timeline logging when admin updates complaint status
- **Notification Badge** — Dynamic unread notification count in sidebar navigation

## Tech Stack

- **Backend:** PHP 7.4+ (MySQLi with prepared statements)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Theme:** Custom blue & white with admin green accent
- **File Storage:** Local uploads directory with secure naming

## Project Structure

```
complaint-management-system/
├── index.php                  # Landing page with redirect logic
├── login.php                  # Login with role-based redirect
├── register.php               # User registration
├── logout.php                 # Session destroy & redirect
├── dashboard.php              # User dashboard
├── add_complaint.php          # Submit complaint (categories + priority + attachments)
├── complaints.php             # User complaints with search/filter
├── view_complaint.php         # Detailed complaint view with timeline & comments
├── add_comment.php            # Handle comment submission (POST handler)
├── notifications.php          # Notification center page
├── reports.php                # Analytics dashboard with charts (admin)
├── admin_dashboard.php        # Admin overview & stats
├── admin_complaints.php       # Admin: manage all complaints with timeline logging
├── admin_users.php            # Admin: manage users & roles
├── config.php                 # Database connection config
├── database.sql               # Full database schema (Phase 1-3)
├── includes/
│   ├── sidebar.php            # Dynamic sidebar with notification badge
│   ├── topbar.php             # Top navigation bar
│   ├── auth_check.php         # User authentication guard
│   ├── admin_check.php        # Admin role guard
│   ├── email_helper.php       # Email notification functions
│   └── notification_helper.php # In-app notification functions
├── uploads/                   # File attachment storage
│   └── .gitkeep
├── assets/
│   ├── css/style.css          # Custom theme styles (Phase 1-3)
│   └── js/script.js           # Form validation & UI helpers
└── README.md
```

## Setup Instructions

### Requirements
- XAMPP / WAMP / MAMP (or any Apache + PHP + MySQL stack)
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Shubh0o7/complaint-management-system.git
   ```

2. **Move to your web server directory:**
   ```bash
   # For XAMPP
   cp -r complaint-management-system /path/to/xampp/htdocs/
   ```

3. **Import the database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `complaint_system` (or let the SQL file create it)
   - Import `database.sql`

4. **Configure database credentials:**
   - Edit `config.php` if your MySQL credentials differ from the defaults:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'complaint_system');
     ```

5. **Set uploads directory permissions:**
   ```bash
   chmod 755 uploads/
   ```

6. **Access the application:**
   - Visit: `http://localhost/complaint-management-system/`
   - Register a new account or use the default admin:
     - Email: `admin@cms.com`
     - Password: `admin123`

### Creating an Admin User

The `database.sql` file includes a default admin account. To manually promote a user:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

## Database Schema

### Tables
- **users** — id, full_name, email, password (hashed), role (user/admin), created_at
- **complaints** — id, user_id (FK), subject, category, priority, description, status, admin_remarks, resolved_at, created_at
- **categories** — id, name, description, created_at
- **complaint_timeline** — id, complaint_id (FK), user_id (FK), action_type, description, created_at
- **complaint_attachments** — id, complaint_id (FK), file_name, original_name, file_size, file_type, uploaded_by, created_at
- **complaint_comments** — id, complaint_id (FK), user_id (FK), comment, created_at
- **notifications** — id, user_id (FK), type, title, message, link, is_read, created_at

## Screenshots

*Coming soon*

## Security Features

- Password hashing with `password_hash()` (bcrypt)
- Prepared statements for all database queries (SQL injection prevention)
- Session-based authentication with role checks
- Input sanitization with `htmlspecialchars()`
- CSRF-safe form handling
- Secure file upload with extension validation and unique naming
- File size limits on uploads

## License

This project is for educational purposes.

## Author

Shubham Shukla
