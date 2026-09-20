# Installation Guide

## Recommended Docker installation

Install Docker Desktop or Docker Engine with Compose support, clone the repository, and run:

```bash
git clone https://github.com/Shubh0o7/complaint-management-system.git
cd complaint-management-system
docker compose up --build
```

Open `http://localhost:8080`. The Compose stack initializes Apache/PHP, MariaDB, the database schema, the application database user, and the uploads volume automatically. No manual SQL import or separately hosted database is required.

The demonstration administrator account is:

| Field | Value |
|---|---|
| Email | `admin@cms.com` |
| Password | `admin123` |

Change this password immediately through **Profile & Password** for any non-demo use.

## Single-container deployment

The application Docker image also contains MariaDB for environments where you want one deployable service with its own database. It initializes the schema from `database.sql` on first boot and uses the `DB_*` environment variables when supplied. Mount `/var/lib/mysql` to persistent storage in production so data survives container replacement.

## Local PHP/MariaDB installation

Install PHP 8.3 with MySQLi, Apache, MariaDB/MySQL, and the standard file-info extension. Create the `complaint_system` database, import `database.sql`, configure the connection values in `config.php` or environment variables, and point Apache’s document root to the repository. Ensure the web server can write only to `logs/` and the private upload directory.

## First-run checklist

After startup, verify that the login page loads, the administrator can reach **Workflow & Accounts**, a complainant account can submit a complaint, and the generated reference number appears in the complaint list. Then run `tests/smoke_test.sh` and review the GitHub Actions result. The GitHub Pages preview is available separately at `https://shubh0o7.github.io/complaint-management-system/`.
