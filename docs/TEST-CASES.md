# Complete Test Case Document

## Test strategy

Testing combines static syntax validation, database schema validation, authenticated workflow smoke tests, authorization checks, upload checks, export checks, and browser-level GitHub Pages verification. Each test is designed to be reproducible against the Docker stack or the local PHP/MariaDB development environment.

| ID | Area | Test steps | Expected result | Status |
|---|---|---|---|---|
| TC-001 | Authentication | Sign in with the seeded administrator account | Correct administrator dashboard opens | Pass |
| TC-002 | Authentication | Sign in with an invalid password | Access is denied without revealing account details | Pass |
| TC-003 | Role routing | Sign in as complainant, department manager, and officer | Each role reaches its scoped dashboard | Pass |
| TC-004 | Registration | Submit valid and invalid registration data | Valid account is created; invalid data is rejected | Pass |
| TC-005 | Password change | Submit current and new passwords | Password changes only after current-password verification | Pass |
| TC-006 | Password recovery | Request reset, use token, reuse token | Expiring token works once and cannot be reused | Pass |
| TC-007 | CSRF | Submit a mutation without the session token | Request is rejected with HTTP 419 | Pass |
| TC-008 | Complaint intake | Submit valid complaint data | Complaint is saved with reference number and SLA due date | Pass |
| TC-009 | Validation | Omit subject, category, or description | Server-side validation returns a clear error | Pass |
| TC-010 | Anonymous complaint | Submit with anonymous option | Staff sees anonymous identity; admin can audit the owner | Pass |
| TC-011 | Search | Search by subject, numeric ID, and `GRV-YYYY-00000` | Matching complaint appears | Pass |
| TC-012 | Attachment security | Upload allowed, oversized, spoofed, and executable files | Only valid MIME-detected files are stored | Pass |
| TC-013 | Attachment authorization | Access another user’s attachment URL | Request is rejected | Pass |
| TC-014 | Routing | Administrator assigns department and officer | Assignment, timeline, notification, and audit event are created | Pass |
| TC-015 | Scoped access | Department/officer opens an unrelated complaint | Request is denied or redirected | Pass |
| TC-016 | Status workflow | Change Pending → In Progress → Resolved | Status, timeline, notification, email record, and resolved time update | Pass |
| TC-017 | Reopen workflow | Change Resolved back to In Progress | `resolved_at` is cleared and timeline records both values | Pass |
| TC-018 | SLA | Use an overdue due date | Dashboard marks the case overdue | Pass |
| TC-019 | Escalation | Escalate an overdue case with a reason | Escalation and notification records are stored | Pass |
| TC-020 | Feedback | Rate a resolved complaint from one to five stars | Rating and comment are stored and visible to analytics | Pass |
| TC-021 | Receipt | Download a complaint receipt | PDF contains reference, status, SLA, and description | Pass |
| TC-022 | CSV export | Administrator downloads CSV | Headers and complaint rows are present; anonymous owners are masked | Pass |
| TC-023 | PDF export | Administrator downloads PDF | Printable report is returned with filtered records | Pass |
| TC-024 | Notifications | Mark one and all notifications as read | Read state and unread count update | Pass |
| TC-025 | Audit dashboard | Open audit dashboard | Logs, overdue cases, department metrics, and officer workload render | Pass |
| TC-026 | Email notifications | Trigger status change and reset request | Delivery attempt is stored and local demo log is written | Pass |
| TC-027 | PHP syntax | Run `tests/smoke_test.sh` | All PHP files pass `php -l` | Pass |
| TC-028 | CI/CD | Push a change or open a pull request | GitHub Actions validates PHP, database, startup, and image build | Pass |
| TC-029 | GitHub Pages | Open the public demo | Static role switching and localStorage interactions work | Pass |
| TC-030 | Data reset | Reset browser demo data | Seed state is restored without affecting the PHP database | Pass |

## Defect recording

Any failed test should be recorded with the test ID, environment, request URL, actor role, expected result, actual result, error log excerpt, severity, owner, and retest result. The repository smoke test is intentionally deterministic and does not claim to replace production penetration testing.
