# Complaint Management System

Role-based PHP and MariaDB grievance-management portal with Docker, CI/CD, audit trails, and an interactive demo.

## Table of contents
- [About](#about)
- [Features](#features)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Quickstart (Docker)](#quickstart-docker)
- [Local development (manual)](#local-development-manual)
- [Configuration](#configuration)
- [Database](#database)
- [CI / CD](#ci--cd)
- [Audit trails & Security](#audit-trails--security)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## About
This project provides a role-based grievance/complaint management portal built with PHP and MariaDB. It includes user roles, audit trails to record actions, Docker support for local development and deployment, CI/CD integration, and an interactive demo environment.

## Features
- Role-based access control (admin, staff, user, etc.)
- Create, update, route and resolve complaints
- Audit trails for important actions
- Dockerized services for easy local setup
- CI/CD pipeline (tests / lint / build)
- Simple web UI (PHP + JS + CSS)

## Tech stack
- PHP (backend)
- MariaDB (database)
- JavaScript (client-side interactivity)
- Docker & Docker Compose
- Shell scripts for automation
- CI (GitHub Actions or similar)

## Requirements
- Docker & Docker Compose (recommended)
- PHP (if running without Docker)
- Composer (if running without Docker)
- A modern web browser

## Quickstart (Docker)
1. Clone the repo:
   git clone https://github.com/Shubh0o7/complaint-management-system.git
   cd complaint-management-system

2. Copy the example environment file and adjust values:
   cp .env.example .env
   # edit .env to set DB credentials and app-specific keys

3. Start services:
   docker compose up -d

4. Install PHP dependencies (if required by your container setup):
   docker compose exec app composer install

5. Run database migrations and seeders:
   docker compose exec app php artisan migrate --seed
   # or use your project's migration/seed commands

6. Open the app in your browser:
   http://localhost:8080  # adjust port according to docker-compose.yml

Notes:
- Replace container/service names/commands above with the ones defined in your docker-compose.yml if they differ (e.g., `php`, `web`, `app`).

## Local development (manual)
If you prefer not to use Docker:
1. Install PHP, Composer and MariaDB locally.
2. Create a database and set credentials in `.env`.
3. Install dependencies:
   composer install
4. Run migrations:
   php artisan migrate --seed
5. Serve the app (example):
   php -S localhost:8080 -t public

## Configuration
- Copy `.env.example` to `.env` and populate database credentials and any secret keys.
- Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, and `DB_NAME` only when overriding the bundled MariaDB connection.
- For production, set appropriate app key and enforce HTTPS.

## Database
- Uses MariaDB. `database.sql` creates the schema, application user, reference data, and demo accounts during first initialization.
- Back up your DB before running destructive commands on production.

## CI / CD
- The repository contains (or is intended to contain) CI configuration to run tests, static analysis, and deployment pipelines.
- Check `.github/workflows/` for the CI workflow definitions.

## Audit trails & Security
- Important actions are recorded in audit logs to provide accountability and traceability.
- Follow security best practices: rotate credentials, use HTTPS, and restrict access to production databases.

## Contributing
Thanks for considering contributing! Suggested workflow:
1. Fork the repo.
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Make changes and include tests where appropriate.
4. Open a pull request describing your changes.

Please include clear commit messages and update the README or docs for any user-facing changes.

## License
Specify your license here (e.g., MIT). If no LICENSE file exists, consider adding one.

## Support
For issues, please open an issue in this repository. For urgent support, include logs, reproduction steps, and environment details (Docker / PHP / MariaDB versions).


## Database

The Docker deployment is self-contained: the application image includes MariaDB, initializes a private `complaint_system` database from `database.sql` on first boot, and stores its data in `/var/lib/mysql`. No separate database service or manual SQL import is required for the single-container deployment. The Docker Compose setup uses the same schema with a dedicated `complaint_db` volume.

To use a managed database instead, set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, and `DB_NAME`; the application will use those values instead of the bundled defaults.

## Role-based login

Students must create their own account from `register.php`; public registration always creates a `user` account and cannot create staff roles. The bundled institutional accounts use separate login IDs and passwords:

| Role | Login ID | Password | Workspace |
|---|---|---|---|
| Administrator | `admin@campus.edu` | `Admin@1234` | `admin_dashboard.php` |
| Department Manager | `manager@campus.edu` | `Manager@1234` | `department_dashboard.php` |
| Complaint Officer | `officer@campus.edu` | `Officer@1234` | `officer_dashboard.php` |

Change the seeded staff passwords before using the system outside a demonstration environment.

## Notification queue worker

Status, comment, assignment, and escalation alerts are enqueued in the `notification_queue` table so web requests stay responsive. Run the worker with:

```bash
php bin/process_notification_queue.php
```

For production, schedule this command from cron or a process supervisor at a short interval. The worker claims pending items, delivers email and push notifications when configured, records the result, and retries transient failures according to the queue policy. Keep the worker on the same PHP configuration and database credentials as the web application.
