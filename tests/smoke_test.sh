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

for file in index.html docs/index.html docs/styles.css docs/app.js docs/.nojekyll database.sql Dockerfile docker-compose.yml .github/workflows/ci-cd.yml; do
  test -e "$file" || fail "Missing required file: $file"
done
pass "Required application, demo, and deployment files"

grep -q "CREATE TABLE.*complaints" database.sql || fail "Complaint schema is missing"
grep -q "CREATE TABLE.*notifications" database.sql || fail "Notification schema is missing"
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
