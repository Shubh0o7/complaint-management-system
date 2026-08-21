# CampusResolve Workflows and Dashboard Guide

CampusResolve is an institutional complaint and grievance management system. The platform separates responsibilities across four authenticated roles so that a complainant can submit and track a case, while authorized staff route, investigate, resolve, and audit it.

## 1. Authentication and access workflow

Every dashboard is protected by authentication. Students must first create an account through `register.php`; the registration is stored in the SQL `users` table with the `user` role. Staff use the seeded demonstration credentials listed below. After a successful login, the PHP application validates the account against the database, creates a session, records the login event, and redirects the user to the role-specific home page. The public GitHub Pages demonstration keeps the same registration-first message for students and does not expose a post-login role selector.

| Role | Login email | Login password | Dashboard destination |
|---|---|---|---|
| Student Dashboard | Register first through `register.php` | Create your own password | `dashboard.php` |
| Administrator | `admin@campus.edu` | `Admin@1234` | `admin_dashboard.php` |
| Department Manager | `manager@campus.edu` | `Manager@1234` | `department_dashboard.php` |
| Complaint Officer | `officer@campus.edu` | `Officer@1234` | `officer_dashboard.php` |

The three staff credentials are college demonstration credentials only and must be replaced before production use. Students are intentionally not seeded: they must register their own account and then log in. A role cannot be selected after login to bypass the account's assigned permissions. Direct access to another PHP dashboard is checked again by server-side role guards and is rejected or redirected when unauthorized.

## 2. Complaint lifecycle workflow

The complaint lifecycle is a controlled sequence of responsibility transfers. Each important transition records a timeline event and can generate an in-app, email, or browser-push notification.

```text
Complainant submits complaint
        │ subject, category, priority, description, optional evidence
        ▼
Administrator reviews and routes
        │ assigns department, checks priority and SLA
        ▼
Department Manager receives queue item
        │ assigns an available complaint officer
        ▼
Complaint Officer investigates
        │ adds remarks, comments, evidence, and status updates
        ▼
Resolved or Rejected
        │ status notification, timeline record, optional escalation history
        ▼
Complainant reviews outcome
        └ submits feedback and 5-star rating after resolution
```

A complaint receives a human-readable reference such as `GRV-2026-00001`. The reference is used for searching, receipts, notifications, exports, and defense demonstrations. SLA rules calculate an expected response and resolution window from the complaint priority. Overdue items are highlighted for operational review and may be escalated.

## 3. Dashboard explanations

### Complainant dashboard

The complainant dashboard is the starting point for students and other campus users. It provides a personal overview of submitted complaints, current statuses, pending work, resolved items, and recent notifications. The **Submit Complaint** flow captures the subject, category, priority, description, and optional evidence. The **My Complaints** area supports reference-number and subject search, filtering, status inspection, comments, timeline review, and post-resolution feedback.

### Administrator dashboard

The administrator dashboard provides institution-wide control. Administrators review all complaints, route cases to departments, manage users and roles, monitor SLA and overdue work, inspect the audit log, review notification-delivery outcomes, and access CSV/PDF reporting. The administrator does not replace the investigation work of the department or officer; instead, the dashboard establishes governance, routing, accountability, and reporting visibility.

### Department Manager dashboard

The department manager dashboard is scoped to the manager's department. It shows the department queue, unassigned items, overdue cases, and officer workload. Managers assign cases only to active officers belonging to the same department, add departmental remarks, monitor progress, and escalate cases that need intervention. The server-side role and department checks prevent the manager from viewing unrelated departmental data.

### Complaint Officer dashboard

The complaint officer dashboard contains only assigned casework. Officers review the case description and evidence, communicate through comments, add investigation remarks, update the status, and record the resolution or rejection decision. The officer view is designed for execution rather than administration, so it does not provide institution-wide user management or unrestricted complaint routing.

## 4. Supporting workflows

### Notification workflow

A complaint submission, assignment, comment, status change, escalation, or resolution can create a notification. In-app notifications are shown in the portal. Email and browser-push attempts are placed in the delivery queue, retried with backoff when appropriate, and logged for administrator review.

### SLA and escalation workflow

The system maps priority to an SLA policy. A scheduled or manual review identifies cases that have passed their due time. Authorized staff can escalate a complaint with a reason and destination user. The escalation remains linked to the complaint and appears in the audit and timeline history.

### Feedback workflow

After a complaint reaches the resolved state, the complainant can submit a rating from one to five stars and an optional comment. Feedback is linked to the complaint and can be used in department and officer performance analysis.

### Audit workflow

Security-sensitive and administrative events are written to the audit log with the actor, action, entity, description, IP address, user agent, and timestamp. This creates an evidence trail for account changes, complaint status changes, assignments, exports, escalations, and notification operations.

## 5. ER diagram explanation

The [detailed ER diagram](diagrams/rendered/er-diagram-detailed.png) models the data layer. `USERS` stores authenticated people and their roles. `DEPARTMENTS` groups staff and receives routed complaints. `COMPLAINTS` is the central transactional entity and connects the complainant, department, assigned officer, priority, status, SLA, and feedback information.

`COMPLAINT_TIMELINE`, `COMPLAINT_COMMENTS`, and `COMPLAINT_ATTACHMENTS` preserve the history and supporting evidence of a case. `NOTIFICATIONS` and `EMAIL_NOTIFICATIONS` deliver and record communication. `COMPLAINT_ESCALATIONS` records intervention history, while `SLA_POLICIES` provides the priority-based resolution rules. `AUDIT_LOGS` records accountable system activity. `USER_PREFERENCES` and `PASSWORD_RESETS` support account management without mixing preference or recovery data into the core complaint row.

The simpler [ER diagram](diagrams/rendered/er-diagram.png) is retained for quick presentation use, while the detailed version is intended for database-design review and project defense.

## 6. Defense explanation

A concise project-defense explanation is: **the complainant creates the case; the administrator routes it; the department manager assigns responsibility; the complaint officer investigates and updates the outcome; the system records the entire journey and notifies the relevant users.** The database design supports this workflow by separating identity, routing, case data, history, evidence, communication, SLA, escalation, and audit concerns into related entities.
