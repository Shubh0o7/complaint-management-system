# CampusResolve SQL database package

`campusresolve.sql` is the portable SQL export for the CampusResolve Online Case and Grievance Management System. It creates the database schema, indexes, constraints, seed departments, categories, SLA policies, and the demonstration staff accounts.

## Import locally

```bash
mariadb -u root -p < sql/campusresolve.sql
```

The repository root `database.sql` remains the canonical deployment bootstrap because Docker Compose and the Render entrypoint mount and execute it automatically. Keep this export synchronized when the schema changes.

## Production note

Change the seeded demonstration passwords before real institutional use. The application stores passwords as one-way hashes and enforces role-scoped access to cases, evidence, comments, escalations, notifications, and audit records.
