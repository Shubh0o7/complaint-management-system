# Deployment Guide

## Deployment architecture

The full PHP application is deployed as the Docker image built from the repository’s `Dockerfile`. MariaDB should run as a managed database or a persistent Compose service. GitHub Actions validates pull requests and publishes the image to GitHub Container Registry on the default branch.

## Container deployment

```bash
docker compose up --build -d
docker compose ps
```

Persist the MariaDB data volume and the secure upload volume. Configure `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and `MAIL_ENABLED` through the hosting platform’s secret manager rather than committing credentials.

## CI/CD deployment

The workflow at `.github/workflows/ci-cd.yml` runs syntax checks, MariaDB initialization, startup smoke tests, and Docker build validation. The default branch publishes an image tagged `latest` and with the commit SHA. A hosting provider deploy hook can be stored as the repository secret `DEPLOY_HOOK_URL`; the workflow invokes it only after the image publication succeeds.

## Email delivery

Set `MAIL_ENABLED=1` and `MAIL_FROM` only when a configured server-side mail transport is available. For a production implementation, replace the adapter with SMTP, an institutional mail relay, or a transactional provider and keep credentials in deployment secrets. The `forgot_password.php` flow creates a SHA-256 token with a one-hour expiry, stores only the token hash in `password_resets`, and marks tokens as single-use after a successful password update. Local and academic-demo mode records messages in `logs/emails.log` and in the `email_notifications` table; the Render college demo also displays the generated one-hour reset link on the recovery page because outbound email is disabled there.

## GitHub Pages limitation

GitHub Pages hosts the browser-only preview in `docs/`. It cannot execute PHP, run sessions, or connect to MariaDB. The preview uses seeded browser data and localStorage; it is suitable for demonstration, while the Docker application is the real server-backed system.

## Production checklist

Change seeded credentials, configure HTTPS, disable directory listing, verify private upload storage, configure SMTP, enable database backups, rotate secrets, restrict database network access, and review the audit log retention policy before accepting real complaints.
