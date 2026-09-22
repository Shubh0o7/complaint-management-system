# Installation Guide

## Recommended Docker installation

Install Docker Desktop or Docker Engine with Compose support, clone the repository, and run:

```bash
git clone https://github.com/Shubh0o7/complaint-management-system.git
cd complaint-management-system
docker compose up --build
```

Open `http://localhost:8080`. The Compose stack initializes Apache/PHP, MariaDB, the database schema, the application database user, and the uploads volume automatically. No manual SQL import or separately hosted database is required.

The seeded institutional accounts are:

| Field | Value |
|---|---|
| Administrator login | `admin@campus.edu` / `Admin@1234` |
| Department Manager login | `manager@campus.edu` / `Manager@1234` |
| Complaint Officer login | `officer@campus.edu` / `Officer@1234` |

Students do not use a shared seeded account. They must select **Sign up**, create a student account with their own email and password, and then sign in. Staff roles cannot be created through public registration. Change the seeded staff passwords immediately through **Profile & Password** for any non-demo use.

## Single-container deployment

The application Docker image also contains MariaDB for environments where you want one deployable service with its own database. It initializes the schema from `database.sql` on first boot and uses the `DB_*` environment variables when supplied. Mount `/var/lib/mysql` to persistent storage in production so data survives container replacement.

## Local PHP/MariaDB installation

Install PHP 8.3 with MySQLi, Apache, MariaDB/MySQL, and the standard file-info extension. Create the `complaint_system` database, import `database.sql`, configure the connection values in `config.php` or environment variables, and point Apache’s document root to the repository. Ensure the web server can write only to `logs/` and the private upload directory.

## First-run checklist

After startup, verify that the login page loads, the administrator can reach **Workflow & Accounts**, a complainant account can submit a complaint, and the generated reference number appears in the complaint list. Then run `tests/smoke_test.sh` and review the GitHub Actions result. The hosted production-style deployment is available at `https://complaint-management-system-ff6u.onrender.com/`.
