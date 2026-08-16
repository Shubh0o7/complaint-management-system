# Online Complaint and Grievance Management System

## Abstract

This project presents a role-based Online Complaint and Grievance Management System for educational institutions. The system provides a structured alternative to paper registers and fragmented email communication by giving complainants a reference number, allowing administrators to route cases, enabling departments and officers to work assigned complaints, and preserving an accountable timeline of activity. The implementation adds service-level tracking, escalation, feedback ratings, secure attachments, audit evidence, notifications, reports, and a browser-based demonstration preview.

## Chapter 1: Introduction

Institutional complaints often require confidentiality, routing, ownership, deadlines, and transparent follow-up. Manual processes make it difficult to measure response time, identify overdue cases, or demonstrate that a complaint was handled fairly. The proposed system centralizes these operations while keeping the user experience accessible to students and staff.

## Chapter 2: Objectives

The objectives are to provide role-based access, reduce manual complaint handling, generate traceable reference numbers, support department and officer workflows, monitor SLA deadlines, collect resolution feedback, protect account and upload operations, and produce auditable reports suitable for institutional review.

## Chapter 3: Requirements and Feasibility

The functional requirements are documented in `docs/SRS.md`. The project is technically feasible with PHP, Apache, MariaDB/MySQL, Bootstrap, and Chart.js. Docker removes environment ambiguity for demonstration, while GitHub Actions provides repeatable validation. The browser-only GitHub Pages preview offers a safe public demonstration without exposing a database.

## Chapter 4: System Design

The ER diagram, DFD Level 0, DFD Level 1, DFD Level 2, use-case diagram, architecture diagram, and sequence diagram are in `docs/diagrams/`. The relational model separates users, complaints, workflow events, notifications, attachments, audit records, SLA policies, escalation records, email attempts, and password-reset tokens. Role checks and prepared SQL statements enforce the main trust boundaries.

## Chapter 5: Implementation

The application uses session-based authentication, password hashing, CSRF tokens for mutations, MIME-based upload validation, random server filenames, role-scoped authorization, reference-number generation, SLA calculation, timeline records, notifications, email logging, PDF/CSV exports, audit analytics, password recovery, profile management, feedback, and escalation pages. The full server-backed installation is containerized; the public GitHub Pages preview implements the key interactions with seeded browser data and localStorage.

## Chapter 6: Testing and Results

The complete test cases are listed in `docs/TEST-CASES.md`, with evidence in `docs/TEST-RESULTS.md`. Testing covers authentication, role boundaries, complaint intake, anonymous mode, CSRF rejection, upload security, status changes, SLA and escalation, feedback, receipts, exports, notifications, audit analytics, CI/CD, and the public demo. Static syntax and schema checks passed during the audit.

## Chapter 7: Security and Privacy

Security controls include password hashing, session regeneration, CSRF protection, prepared statements, server-side MIME detection, restricted upload storage, authorized download, role-scoped queries, one-time expiring password-reset tokens, and audit records. Anonymous complaints hide complainant identity from staff views while retaining administrative accountability. A real deployment still requires HTTPS, secrets management, retention policy, independent penetration testing, and a production mail transport.

## Chapter 8: Conclusion

The project demonstrates a complete institutional grievance workflow rather than a simple CRUD form. It combines process design, database modelling, role-based authorization, security controls, operational monitoring, reporting, documentation, and a public demonstration surface. The system is appropriate for academic evaluation and provides a clear foundation for future institutional integration.

## Appendix

Installation: `docs/INSTALLATION.md`  
Deployment: `docs/DEPLOYMENT.md`  
Database design: `docs/DATABASE-DESIGN.md`  
Future scope: `docs/FUTURE-SCOPE.md`  
Test cases: `docs/TEST-CASES.md`  
Diagrams: `docs/diagrams/`
