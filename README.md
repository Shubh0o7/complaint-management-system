# Complaint Management System — Phase 1

A clean, Bootstrap 5-based complaint management system with user authentication, a dashboard with sidebar + profile, and a complaint submission/listing module. Built with PHP and MySQL.

## Features (Phase 1)

- **Authentication**: Register, Login, Logout with `password_hash()` / `password_verify()` and prepared statements
- **Dashboard**: Welcome banner, profile card, complaint count stats
- **Complaint Module**: Submit new complaints and view a table of your own complaints
- **UI**: Bootstrap 5 with a custom blue & white theme, responsive sidebar layout
- **Security**: Session guards, prepared statements (SQL injection prevention), XSS protection via `htmlspecialchars()`

## File Structure

```
complaint-management-system/
├── index.php              # Landing page (redirects if logged in)
├── login.php              # Login form + auth logic
├── register.php           # Registration form + validation
├── logout.php             # Session destroy + redirect
├── dashboard.php          # Main dashboard with stats & profile
├── add_complaint.php      # Complaint submission form
├── complaints.php         # List of user's complaints
├── config.php             # MySQLi connection (edit credentials here)
├── database.sql           # SQL schema (users + complaints tables)
├── includes/
│   ├── sidebar.php        # Shared sidebar navigation
│   ├── topbar.php         # Shared top navigation bar
│   └── auth_check.php     # Authentication guard partial
├── assets/
│   ├── css/style.css      # Custom blue/white theme styles
│   └── js/script.js       # Form validation + alert auto-dismiss
└── README.md              # This file
```

## Setup (XAMPP / WAMP)

1. **Import the database**:  
   Open phpMyAdmin → Import → select `database.sql` → Go.  
   This creates the `complaint_system` database with `users` and `complaints` tables.

2. **Place the project**:  
   Copy the `complaint-management-system/` folder into your server's `htdocs/` (XAMPP) or `www/` (WAMP) directory.

3. **Configure database credentials**:  
   Open `config.php` and adjust `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` if your setup differs from the defaults (`localhost`, `root`, `''`, `complaint_system`).

4. **Run**:  
   Start Apache and MySQL, then visit:  
   ```
   http://localhost/complaint-management-system/index.php
   ```

## Out of Scope (Phase 1)

The following are **deliberately not included** in this phase:

- Admin panel / admin roles
- Complaint editing or deletion
- File attachments on complaints
- Email notifications
- Password reset flow
- Pagination on complaint listing
- API endpoints

These may be added in future phases.

## Tech Stack

- **Backend**: PHP 7.4+ (procedural with MySQLi)
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: Bootstrap 5.3, Bootstrap Icons
- **JavaScript**: Vanilla JS (no frameworks)

## License

MIT
