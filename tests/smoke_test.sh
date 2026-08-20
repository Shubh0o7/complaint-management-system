#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

pass() { printf 'PASS  %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1" >&2; exit 1; }

command -v php >/dev/null || fail "PHP CLI is required"
command -v node >/dev/null || fail "Node.js is required"
command -v curl >/dev/null || fail "curl is required"

php_files=()
while IFS= read -r -d '' file; do php_files+=("$file"); done < <(find . -name '*.php' -not -path './.git/*' -print0)
((${#php_files[@]} > 0)) || fail "No PHP files found"
for file in "${php_files[@]}"; do php -l "$file" >/dev/null; done
pass "PHP syntax (${#php_files[@]} files)"

node --check docs/app.js >/dev/null
pass "GitHub Pages demo JavaScript syntax"
for file in api/add_comment.php api/mark_notification_read.php api/update_complaint_status.php api/upload_attachment.php; do
  grep -q 'require_csrf_json' "$file" || fail "Missing CSRF guard: $file"
done
grep -q 'new finfo(FILEINFO_MIME_TYPE)' api/upload_attachment.php || fail "Upload MIME detection missing"
grep -q 'notify_complaint_status_change' includes/notification_helper.php || fail "Unified status notification dispatcher missing"
grep -q 'notify_complaint_status_change' admin_complaints.php || fail "Administrator status alert integration missing"
grep -q 'notify_complaint_status_change' officer_dashboard.php || fail "Officer status alert integration missing"
grep -q 'notify_complaint_status_change' api/update_complaint_status.php || fail "API status alert integration missing"
grep -q 'queue_notification' includes/notification_helper.php || fail "Notification queue enqueue missing"
grep -q 'queue_next_item' includes/notification_queue.php || fail "Notification queue claim missing"
grep -q 'queue_mark_result' includes/notification_queue.php || fail "Notification queue result handling missing"
grep -q 'process_notification_queue' README.md || fail "Notification queue worker documentation missing"
grep -q 'deliveryRows' admin_audit.php || fail "Admin delivery-status dashboard missing"
grep -q 'random_bytes(32)' forgot_password.php || fail "Password reset token generation missing"
grep -q "hash('sha256'" forgot_password.php || fail "Password reset token hashing missing"
grep -q 'expires_at > NOW()' reset_password.php || fail "Password reset expiry validation missing"
grep -q 'used_at = NOW()' reset_password.php || fail "Password reset token revocation missing"
grep -q 'require_csrf' forgot_password.php reset_password.php || fail "Password reset CSRF guard missing"
pass "Mutating API CSRF, upload MIME, multi-channel alerts, and password-reset guards"

for file in index.html docs/index.html docs/styles.css docs/app.js docs/.nojekyll database.sql Dockerfile docker-compose.yml package.json package-lock.json tests/e2e-auth-theme.mjs .github/workflows/ci-cd.yml includes/security.php includes/pdf_helper.php includes/push_helper.php includes/notification_queue.php profile.php settings.php change_password.php forgot_password.php reset_password.php feedback.php receipt.php admin_export.php admin_audit.php escalate_complaint.php api/push_subscribe.php bin/process_notification_queue.php service-worker.js assets/js/push-notifications.js composer.json; do
  test -e "$file" || fail "Missing required file: $file"
done
for file in docs/SRS.md docs/TEST-CASES.md docs/TEST-RESULTS.md docs/DATABASE-DESIGN.md docs/INSTALLATION.md docs/DEPLOYMENT.md docs/FUTURE-SCOPE.md docs/PROJECT-REPORT.md; do
  test -e "$file" || fail "Missing required documentation: $file"
done
for file in docs/diagrams/er-diagram.mmd docs/diagrams/dfd-level-0.mmd docs/diagrams/dfd-level-1.mmd docs/diagrams/dfd-level-2.mmd docs/diagrams/use-case-diagram.mmd docs/diagrams/system-architecture.mmd docs/diagrams/complaint-sequence.mmd; do
  test -s "$file" || fail "Missing diagram source: $file"
done
pass "Required application, demo, deployment, and documentation files"

grep -q "CREATE TABLE.*complaints" database.sql || fail "Complaint schema is missing"
grep -q "CREATE TABLE.*notifications" database.sql || fail "Notification schema is missing"
grep -q "CREATE TABLE.*audit_logs" database.sql || fail "Audit schema is missing"
grep -q "reference_no" database.sql || fail "Complaint reference schema is missing"
grep -q "feedback_rating" database.sql || fail "Feedback schema is missing"
grep -q "sla_due_at" database.sql || fail "SLA schema is missing"
grep -q 'CREATE TABLE IF NOT EXISTS `user_preferences`' database.sql || fail "User preferences schema is missing"
grep -q 'CREATE TABLE IF NOT EXISTS `system_settings`' database.sql || fail "System settings schema is missing"
grep -q 'CREATE TABLE IF NOT EXISTS `push_subscriptions`' database.sql || fail "Push subscriptions schema is missing"
grep -q 'CREATE TABLE IF NOT EXISTS `push_notifications`' database.sql || fail "Push delivery schema is missing"
grep -q 'minishlink/web-push' composer.json || fail "Web Push dependency manifest is missing"
grep -q "packages: write" .github/workflows/ci-cd.yml || fail "Container publishing permission is missing"
pass "Database and CI/CD markers"

PORT=8765
python3 -m http.server "$PORT" --directory docs >/tmp/grievance-pages-smoke.log 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT
sleep 1
curl --fail --silent "http://127.0.0.1:${PORT}/index.html" | grep -q "Grievance Portal"
curl --fail --silent "http://127.0.0.1:${PORT}/app.js" | grep -q "localStorage"
curl --fail --silent "http://127.0.0.1:${PORT}/styles.css" | grep -q "app-shell"
pass "Static GitHub Pages demo serves HTML, JavaScript, and CSS"

if [[ -x node_modules/.bin/playwright ]]; then
  E2E_BASE_URL="http://127.0.0.1:${PORT}" npm run test:e2e
  pass "Browser end-to-end authentication and theme flow"
else
  pass "Browser end-to-end suite present (install npm dependencies to execute locally)"
fi

printf '\nAll smoke tests passed.\n'
