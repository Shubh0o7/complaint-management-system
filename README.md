# Complaint Management System

A full-featured web-based complaint management system built with PHP, MySQL, and Bootstrap 5. Features a clean blue & white theme, role-based access control, and a comprehensive admin panel.

## Features

### Phase 1 — Core System
- **User Authentication** — Register, Login, Logout with `password_hash()`/`password_verify()` and prepared statements
- **Dashboard** — Welcome banner, profile card, complaint statistics by status
- **Submit Complaints** — Form with subject, category, priority, and description
- **View Complaints** — User-scoped complaint listing with search & filter
- **Session Management** — Auth guards on all protected pages

### Phase 2 — Admin Panel & Enhanced Features
- **Admin Dashboard** — Overview stats (total users, complaints, pending, critical), recent complaints table
- **Admin Complaint Management** — View all complaints, update status (Pending → In Progress → Resolved), filter by status/category/priority, search
- **Admin User Management** — View all registered users, see complaint counts per user, promote/demote admin role
- **Complaint Categories** — Database-driven categories (Academic, Infrastructure, Faculty, Hostel, Transport, Fees, Other)
- **Priority Levels** — Low, Medium, High, Critical with color-coded badges
- **Status Tracking** — Pending, In Progress, Resolved with admin-controlled updates
- **Search & Filter** — Filter complaints by status, category, priority; search by subject/description
- **Role-Based Access** — Admin vs User roles with separate dashboards and navigation
- **Dynamic Sidebar** — Shows admin links only for admin users

## Tech Stack

- **Backend:** PHP 7.4+ (MySQLi with prepared statements)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5.3, Bootstrap Icons
- **Theme:** Custom blue & white with admin green accent

## Project Structure

```
complaint-management-system/
├── index.php                  # Landing page with redirect logic
├── login.php                  # Login with role-based redirect
├── register.php               # User registration
├── logout.php                 # Session destroy & redirect
├── dashboard.php              # User dashboard
├── add_complaint.php          # Submit complaint (categories + priority)
├── complaints.php             # User complaints with search/filter
├── admin_dashboard.php        # Admin overview & stats
├── admin_complaints.php       # Admin: manage all complaints
├── admin_users.php            # Admin: manage users & roles
├── config.php                 # Database connection config
├── database.sql               # Full database schema (Phase 2)
├── includes/
│   ├── sidebar.php            # Dynamic sidebar (user/admin)
│   ├── topbar.php             # Top navigation bar
│   ├── auth_check.php         # User authentication guard
│   └── admin_check.php        # Admin role guard
├── assets/
│   ├── css/style.css          # Custom theme styles
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

5. **Access the application:**
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
- **complaints** — id, user_id (FK), subject, category, priority, description, status, created_at
- **categories** — id, name, description, created_at

## Screenshots

*Coming soon*

## Security Features

- Password hashing with `password_hash()` (bcrypt)
- Prepared statements for all database queries (SQL injection prevention)
- Session-based authentication with role checks
- Input sanitization with `htmlspecialchars()`
- CSRF-safe form handling

## License

This project is for educational purposes.

## Author

Shubham Shukla
