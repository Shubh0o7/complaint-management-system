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

for file in index.html docs/index.html docs/styles.css docs/app.js docs/.nojekyll database.sql Dockerfile docker-compose.yml .github/workflows/ci-cd.yml includes/security.php includes/pdf_helper.php profile.php change_password.php forgot_password.php reset_password.php feedback.php receipt.php admin_export.php admin_audit.php escalate_complaint.php; do
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

printf '\nAll smoke tests passed.\n'
