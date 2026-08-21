# College Project Documentation

This directory contains the design evidence and operational documentation for the Online Complaint and Grievance Management System. The public GitHub Pages preview is branded **CampusResolve — Student Grievance Portal**, while the formal academic and server-backed application title remains unchanged.

| Document | Purpose |
|---|---|
| [SRS](SRS.md) | Functional and non-functional requirements |
| [Project Report](PROJECT-REPORT.md) | Formal academic report narrative |
| [Test Cases](TEST-CASES.md) | Complete test-case catalogue |
| [Test Results](TEST-RESULTS.md) | Reproducible validation evidence |
| [Database Design](DATABASE-DESIGN.md) | Entities, relationships, constraints, and security controls |
| [Workflows and Dashboards](WORKFLOWS-AND-DASHBOARDS.md) | Authentication, complaint lifecycle, role responsibilities, and ER explanation |
| [Installation](INSTALLATION.md) | Docker and local setup |
| [Deployment](DEPLOYMENT.md) | CI/CD, container hosting, email, and production checklist |
| [Future Scope](FUTURE-SCOPE.md) | Realistic next steps beyond the academic build |
| [Diagrams](diagrams/) | ER, DFD 0–2, use case, architecture, and sequence diagrams |
| [Screenshots](screenshots/modules/) | Login, signup, and four role-specific interface captures |

The public browser demo is available at [shubh0o7.github.io/complaint-management-system](https://shubh0o7.github.io/complaint-management-system/). Its authentication screen follows a clean split Login/Sign up layout inspired by the supplied reference, but GitHub Pages is a static preview and does not connect directly to SQL. The complete PHP application uses direct MySQL/MariaDB persistence: `register.php` creates Student Dashboard accounts in the `users` table, and the authenticated dashboards read and write Cases through PHP sessions and prepared SQL queries. Review [Workflows and Dashboards](WORKFLOWS-AND-DASHBOARDS.md) for the ER model and the complete role-by-role explanation.

| Demo role | Email | Password |
|---|---|---|
| Student Dashboard | Register first through `register.php` | Create your own password |
| Administrator | `admin@campus.edu` | `Admin@1234` |
| Department Manager | `manager@campus.edu` | `Manager@1234` |
| Complaint Officer | `officer@campus.edu` | `Officer@1234` |

The screenshots in [screenshots](screenshots/) cover the reference-inspired Login and Sign up screens, while [screenshots/modules](screenshots/modules/) contains the four role-specific layouts. They were refreshed after the role-login, slideable-sidebar, and mobile-backdrop updates.
