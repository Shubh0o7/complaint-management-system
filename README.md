# Complaint Management System

A modern, role-based **online complaint and grievance management portal** built with PHP, MySQL/MariaDB, Bootstrap 5, and Chart.js. The system manages the complete complaint lifecycle—from submission and routing to investigation, resolution, notifications, and reporting—in a clean government and college-portal style interface.

> **Project status:** The repository includes the application, database schema, Docker setup, and GitHub Actions CI/CD workflow. A fresh environment can be started with one Docker Compose command.

## Contents

- [What the system does](#what-the-system-does)
- [Role-based workflow](#role-based-workflow)
- [Features](#features)
- [Technology](#technology)
- [Quick start](#quick-start)
- [Default administrator](#default-administrator)
- [Project structure](#project-structure)
- [Database model](#database-model)
- [Automated testing and deployment](#automated-testing-and-deployment)
- [Security notes](#security-notes)
- [Useful commands](#useful-commands)
- [License](#license)

## What the system does

The portal gives each participant a focused workspace. Complainants can submit cases with descriptions and attachments, follow progress through a timeline, communicate with the responsible team, and receive in-app notifications. Administrators manage the entire system, while departments and complaint officers work only on the cases assigned to them.

The interface uses responsive navigation, dashboard cards, searchable tables, status and priority badges, activity timelines, notifications, modal actions, and analytics views. The design intentionally favors clarity and operational usefulness over decorative complexity.

## Role-based workflow

```text
Complainant
    │ Submit complaint
    ▼
Administrator
    │ Route to department
    ▼
Department manager
    │ Assign complaint officer
    ▼
Complaint officer
    │ Investigate, communicate, add remarks, update status
    ▼
Resolution
    │ Notify complainant and record timeline activity
    ▼
Complainant
    └ View resolution and complaint history
```

| Role | Workspace | Access scope |
| --- | --- | --- |
| **User** | `dashboard.php`, `complaints.php`, `view_complaint.php` | Own complaints, comments, attachments, notifications, and timelines |
| **Admin** | `admin_dashboard.php`, `admin_complaints.php`, `admin_assignments.php`, `reports.php` | Complete system oversight, routing, account management, and analytics |
| **Department manager** | `department_dashboard.php` | Complaints routed to the manager's department and available officers |
| **Complaint officer** | `officer_dashboard.php` | Complaints assigned to the officer and their investigation workflow |

## Features

### Complainant experience

Users can register and log in securely, submit complaints with a subject, category, priority, description, and attachments, and search or filter their complaint history. Each complaint has a detailed page with status, metadata, comments, uploaded evidence, notifications, and an activity timeline.

### Department operations

Department managers see only complaints routed to their department. They can filter the queue, inspect complainant information, assign available officers, add departmental remarks, and monitor pending, active, resolved, and rejected work.

### Officer operations

Complaint officers see only their assigned cases. They can review the complete complaint record, communicate through comments, add investigation or resolution remarks, and move a case through `Pending`, `In Progress`, `Resolved`, or `Rejected`. Each important action creates a timeline entry and notifies the complainant.

### Administration and reporting

Administrators can review all complaints, route cases to departments, create department-manager and officer accounts, manage user roles, review activity, and access Chart.js reporting views for system-level trends and performance.

## Technology

| Layer | Implementation |
| --- | --- |
| Frontend | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, vanilla JavaScript |
| Backend | PHP 8.3-compatible server-side application |
| Database | MySQL or MariaDB with MySQLi prepared statements |
| Charts | Chart.js |
| Authentication | PHP sessions and `password_hash()` / `password_verify()` |
| File storage | Secure local storage under `uploads/` |
| Local runtime | Docker Compose with PHP-Apache and MariaDB |
| CI/CD | GitHub Actions with PHP linting, database smoke tests, and GHCR publishing |

## Quick start

### Recommended: Docker Compose

The repository includes everything required to initialize the application database and run the PHP server. With Docker Desktop or Docker Engine installed, run:

```bash
git clone https://github.com/Shubh0o7/complaint-management-system.git
cd complaint-management-system
docker compose up --build
```

Open [http://localhost:8080](http://localhost:8080) after the containers become healthy. The database schema, application database user, departments, categories, uploads directory, and default administrator account are initialized automatically.

To stop the application, press `Ctrl+C`. To remove the database volume and start from a completely fresh state, run:

```bash
docker compose down -v
docker compose up --build
```

### Existing PHP and MariaDB installation

The application also works with a conventional Apache/PHP and MySQL/MariaDB installation. Import `database.sql` as a database administrator, then provide connection values through environment variables when needed:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_USER=complaint_user
export DB_PASS=complaint_pass
export DB_NAME=complaint_system
```

The default configuration already matches the credentials created by the included schema. Use a web server document root pointing at the repository and ensure that `uploads/` is writable by the web-server process.

## Default administrator

The seeded development administrator is:

| Field | Value |
| --- | --- |
| Email | `admin@cms.com` |
| Password | `admin123` |

Change this password immediately before using the application with real data. After signing in, open **Workflow & Accounts** to create department-manager and complaint-officer accounts and route complaints.

## Project structure

```text
.
├── index.php                         Landing page and role redirect
├── login.php / register.php           Authentication screens
├── dashboard.php                      Complainant dashboard
├── add_complaint.php                  Complaint submission form
├── complaints.php                     Complainant complaint list
├── view_complaint.php                 Complaint detail, comments, files, timeline
├── department_dashboard.php           Department-scoped queue and officer assignment
├── officer_dashboard.php              Officer-scoped investigation queue
├── admin_dashboard.php                System overview
├── admin_complaints.php               Administrator complaint management
├── admin_assignments.php              Routing and role-account console
├── admin_users.php                    User and role management
├── reports.php                        Analytics dashboard
├── api/                               JSON and form-processing endpoints
├── includes/                          Auth guards, layout, notifications, and role helpers
├── assets/                            Shared styles and browser scripts
├── uploads/                           Local attachment storage
├── database.sql                       Complete schema and seed data
├── Dockerfile                         PHP-Apache application image
├── docker-compose.yml                 Application plus MariaDB stack
└── .github/workflows/ci-cd.yml        Automated testing and deployment workflow
```

## Database model

The schema is organized around the complaint lifecycle. `users` stores all four role types and optional department membership. `departments` stores routing groups. `complaints` stores the core case, assignment, status, priority, and remarks. `complaint_timeline`, `complaint_comments`, `complaint_attachments`, and `notifications` provide traceability and communication.

| Table | Responsibility |
| --- | --- |
| `users` | Authentication, roles, department membership, and account state |
| `departments` | Department directory and active routing groups |
| `categories` | Complaint categories used by submission forms |
| `complaints` | Complaint records, assignments, statuses, priorities, and remarks |
| `complaint_timeline` | Immutable activity and status history |
| `complaint_comments` | Conversation between complainants and staff |
| `complaint_attachments` | Evidence and supporting files |
| `notifications` | In-app status, assignment, comment, and system alerts |

## Automated testing and deployment

The workflow in `.github/workflows/ci-cd.yml` runs on pull requests and pushes to `master` or `main`. It installs PHP 8.3, starts a MariaDB service, imports the repository schema, lints all PHP files, starts the application, verifies the login endpoint, and checks the administrator dashboard.

After tests pass on the default branch, the workflow builds and publishes a Docker image to the repository's GitHub Container Registry package. It tags the image as `latest` and with the commit SHA. To trigger deployment on a hosting provider that exposes a deploy hook, add a repository secret named `DEPLOY_HOOK_URL`; the workflow calls it after publishing the image. Without that optional secret, testing and image publishing still complete normally.

## Security notes

The application uses prepared MySQLi statements, password hashing, PHP session authentication, role guards, server-side role validation, scoped complaint queries, unique upload names, attachment metadata, and timeline records. For production use, place secrets outside version control, enforce HTTPS, change the seeded administrator password, restrict upload file types and sizes according to policy, disable directory listing, and use a private database account with only the required permissions.

The repository's default credentials are intended for development and demonstration only. They must not be retained in a public or production deployment.

## Useful commands

```bash
# Start the complete application stack
docker compose up --build

# Stop the stack
docker compose down

# Reset the local database volume
docker compose down -v

# Validate PHP syntax locally
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l

# Inspect the current repository state
git status
```

## License

This project is provided for educational and demonstration purposes. Add an explicit license before distributing it as a reusable software product.


## Public project demo

The repository includes a GitHub Pages-compatible interactive preview at [https://shubh0o7.github.io/complaint-management-system/](https://shubh0o7.github.io/complaint-management-system/). Visitors can switch between the four roles, view dashboards, submit sample complaints, update statuses, inspect timelines, and see notifications. Demo changes are stored in the visitor's browser through `localStorage` and do not modify the production database.

GitHub Pages cannot execute PHP or MySQL. The interactive preview is therefore the presentation layer for the college project, while the complete server-backed application remains available through Docker Compose for environments that support PHP and MariaDB.
