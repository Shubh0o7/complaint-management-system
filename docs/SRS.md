# Software Requirements Specification

## 1. Purpose

The Online Complaint and Grievance Management System provides a controlled digital workflow for submitting, routing, investigating, resolving, auditing, and evaluating institutional complaints. The system replaces informal email or paper handling with reference-based tracking, role-specific workspaces, service-level monitoring, and accountable records.

## 2. Scope

The system supports four operational roles: complainant, administrator, department manager, and complaint officer. It provides authentication, profile management, complaint creation, optional anonymous submission, secure attachments, reference numbers, SLA deadlines, assignment, escalation, comments, notifications, email logging, resolution feedback, CSV/PDF reports, and audit history. A browser-only GitHub Pages demo is maintained separately from the PHP/MariaDB production application.

## 3. Functional requirements

| ID | Requirement | Acceptance criterion |
|---|---|---|
| FR-01 | Authenticate users and route by role | Valid users reach their correct dashboard; inactive users are rejected |
| FR-02 | Submit complaints | Required fields are validated, a `GRV-YYYY-00000` reference is created, and an SLA deadline is stored |
| FR-03 | Support anonymous complaints | Staff views display “Anonymous complainant”; administrators retain the accountable user record |
| FR-04 | Manage workflow | Administrators route complaints; departments and officers update scoped cases |
| FR-05 | Track SLA | Pending and active cases display due dates and overdue indicators |
| FR-06 | Escalate complaints | An escalation record stores reason, actor, recipient, and timestamp |
| FR-07 | Notify stakeholders | In-app notifications and database-backed email delivery records are created |
| FR-08 | Collect feedback | Resolved complainants can submit a one-to-five-star rating and comment |
| FR-09 | Produce reports | Administrators can download filtered complaint data as CSV or PDF |
| FR-10 | Maintain auditability | Security-sensitive and workflow mutations write audit records |
| FR-11 | Protect mutations | Authenticated POST forms and APIs require CSRF tokens |
| FR-12 | Protect uploads | MIME detection, size limits, random filenames, restricted storage, and authorized download are enforced |

## 4. Non-functional requirements

The application shall use responsive Bootstrap interfaces, prepared SQL statements for user-controlled values, password hashing, role-scoped authorization, UTF-8 database encoding, and clear error responses. Normal dashboard requests should complete within two seconds on the local Docker stack. Audit and notification records should be queryable without exposing passwords or reset tokens.

## 5. Roles and permissions

| Role | Primary responsibilities |
|---|---|
| Complainant | Submit, search, track, comment, download receipts, and rate resolved complaints |
| Administrator | Manage users, route cases, update statuses, escalate, export, and review audit analytics |
| Department manager | Review department-scoped cases, assign officers, add remarks, and monitor SLA health |
| Complaint officer | Work assigned cases, add investigation remarks, update progress, and resolve cases |

## 6. Assumptions and constraints

The full application requires PHP 8.3+, Apache, and MariaDB/MySQL. Email delivery is logged locally by default and can be connected to a configured mail transport. GitHub Pages can host only the static interactive preview and cannot execute PHP or connect to MariaDB.
