# Database Design

## Design principles

The schema uses a relational model with foreign keys for workflow ownership, normalized department and user records, append-only timeline and audit evidence, and explicit tables for notifications, email attempts, SLA policies, password resets, and escalations. User passwords are stored only as one-way password hashes.

## Core entities

| Table | Purpose | Important relationships |
|---|---|---|
| `users` | Accounts, roles, departments, profile details, and login metadata | Belongs to `departments`; owns complaints and audit events |
| `departments` | Institutional routing units | Receives complaints and owns department managers/officers |
| `complaints` | Primary grievance record | References owner, department, officer, SLA policy outcome, feedback, and escalation state |
| `categories` | Controlled complaint categories | Used by complaint intake and filtering |
| `complaint_attachments` | Metadata for securely stored files | Belongs to a complaint and uploader |
| `complaint_comments` | Conversation records | Belongs to a complaint and author |
| `complaint_timeline` | Status, assignment, and activity history | Belongs to a complaint and actor |
| `notifications` | In-app notifications | Targets a user and optionally a complaint |
| `audit_logs` | Accountability events | Records actor, action, entity, description, IP, and user agent |
| `sla_policies` | Priority-to-resolution-hour rules | Used to calculate complaint due dates |
| `complaint_escalations` | Escalation records | Links complaint, source actor, recipient, and reason |
| `email_notifications` | Email delivery attempts | Records recipient, content, status, error, and sent time |
| `password_resets` | Hashed, expiring, one-time reset tokens | Belongs to a user |

## Integrity and security controls

Reference numbers are unique in the form `GRV-YYYY-00001`. Complaint status and priority values are constrained by application validation and the schema’s enumerated fields. Foreign keys use cascading deletes only where child records have no independent value, while audit evidence is retained with the actor reference nullable for system events. Indexes cover status, priority, department, officer, due date, reference number, notification read state, audit chronology, and reset-token lookup.

The application never trusts the browser-provided MIME type for secure upload decisions. It stores a random server filename, restricts the upload directory, and exposes files only through an authorization-aware download endpoint.

## Seed data

The schema provisions the standard complaint categories, departments, SLA policies, and a demonstration administrator account. The documented password is intended for academic demonstration only and must be changed before any real deployment.
