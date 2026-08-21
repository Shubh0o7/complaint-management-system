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
| [Screenshots](screenshots/modules/) | Four role-specific interface captures |

The public browser demo is available at [shubh0o7.github.io/complaint-management-system](https://shubh0o7.github.io/complaint-management-system/). The demo uses strict role-specific credentials and a dedicated shared complaint table, so complaints submitted from one device can be viewed from another. A local browser cache is retained only as a fallback; the full server-backed application is started with Docker. Review [Workflows and Dashboards](WORKFLOWS-AND-DASHBOARDS.md) for the ER model and the complete role-by-role explanation.

| Demo role | Email | Password |
|---|---|---|
| Complainant | `student@campus.edu` | `Student@1234` |
| Administrator | `admin@campus.edu` | `Admin@1234` |
| Department Manager | `manager@campus.edu` | `Manager@1234` |
| Complaint Officer | `officer@campus.edu` | `Officer@1234` |

The role screenshots in [screenshots/modules](screenshots/modules/) were refreshed after the shared-storage, role-login, slideable-sidebar, and mobile-backdrop updates.
