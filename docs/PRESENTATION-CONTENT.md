## Cover
Online Complaint & Grievance Management System
A role-based, auditable grievance workflow for colleges
College Project Presentation

## Slide 1
The problem: complaints are difficult to track
- Paper registers and fragmented email create weak visibility.
- Students need a reference number and transparent status.
- Administrators need routing, ownership, SLA deadlines, and evidence.

## Slide 2
The proposed solution: one accountable workflow
- Complainant submits a complaint with category, priority, description, and evidence.
- Administrator routes the case to a department.
- Department manager assigns an officer; the officer investigates and resolves.
- The timeline, notifications, audit logs, and feedback preserve accountability.

## Slide 3
Four role workspaces, one shared process
- Complainant: submit, search, comment, download receipts, and rate resolutions.
- Administrator: manage users, route cases, export reports, and review audit analytics.
- Department manager: review scoped workload, assign officers, and monitor SLA health.
- Complaint officer: investigate assigned cases and record progress.

## Slide 4
The data model makes the workflow traceable
- Core entities: users, departments, complaints, attachments, comments, and timeline.
- Governance entities: audit logs, SLA policies, escalations, notifications, and email attempts.
- Complaint references follow the institutional pattern `GRV-YYYY-00001`.

## Slide 5
Security is part of the design
- Password hashing, session regeneration, role-scoped authorization, and CSRF protection.
- Server-side MIME detection, random upload names, restricted storage, and authorized downloads.
- Expiring one-time password-reset tokens and audit records for sensitive actions.
- Anonymous complaints hide identity from staff while preserving administrative accountability.

## Slide 6
SLA and escalation turn data into action
- Priority policies calculate a resolution deadline at intake.
- Active cases show overdue indicators in administrator and role dashboards.
- Escalation records capture reason, actor, recipient, timestamp, and notification.
- Department and officer analytics show workload and resolution performance.

## Slide 7
Reports and communication support institutional review
- Administrators can export complaint records as CSV or PDF.
- Complainants can download a PDF receipt containing reference, status, SLA, and description.
- In-app notifications and database-backed email records preserve delivery evidence.
- Resolved cases support one-to-five-star feedback and comments.

## Slide 8
Testing and automation make the project reproducible
- Repository smoke test covers 45 PHP files, JavaScript syntax, schema markers, documentation, and static demo serving.
- Workflow checks cover PHP validation, MariaDB initialization, startup, login, dashboard smoke tests, and Docker publishing.
- Authenticated matrix tests cover all four roles, unauthorized access, complaint creation, resolution, notifications, and exports.

## Slide 9
Deployment and demonstration
- Full application: one-command Docker Compose stack with PHP-Apache and MariaDB.
- GitHub Actions: automated validation and GitHub Container Registry publishing.
- GitHub Pages: public browser-only preview for immediate evaluation without database setup.
- Documentation includes SRS, project report, database design, test cases, diagrams, installation, deployment, and future scope.

## Slide 10
Conclusion: more than CRUD
- The system demonstrates process design, security, database modelling, analytics, reporting, and documentation.
- Every complaint has an owner, status, deadline, evidence trail, and feedback loop.
- The project is ready for academic demonstration and structured future institutional integration.
