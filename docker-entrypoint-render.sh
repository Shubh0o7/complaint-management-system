#!/usr/bin/env bash
set -Eeuo pipefail

DATA_DIR="/var/lib/mysql"
INIT_MARKER="${DATA_DIR}/.campusresolve_initialized"

if [[ ! -d "${DATA_DIR}/mysql" ]]; then
  mkdir -p "${DATA_DIR}"
  chown -R mysql:mysql "${DATA_DIR}"
  mariadb-install-db --user=mysql --datadir="${DATA_DIR}" >/tmp/mariadb-install.log 2>&1
fi

mariadbd --user=mysql --datadir="${DATA_DIR}" --bind-address=127.0.0.1 --skip-name-resolve >/tmp/mariadb.log 2>&1 &
DB_PID=$!
cleanup() {
  kill "${DB_PID}" 2>/dev/null || true
}
trap cleanup EXIT TERM INT

for attempt in {1..60}; do
  if mariadb-admin ping --user=root --protocol=socket >/dev/null 2>&1; then
    break
  fi
  if ! kill -0 "${DB_PID}" 2>/dev/null; then
    cat /tmp/mariadb.log >&2 || true
    exit 1
  fi
  sleep 1
done

if ! mariadb-admin ping --user=root --protocol=socket >/dev/null 2>&1; then
  cat /tmp/mariadb.log >&2 || true
  exit 1
fi

if [[ ! -f "${INIT_MARKER}" ]]; then
  mariadb --user=root --protocol=socket < /var/www/html/database.sql
  touch "${INIT_MARKER}"
fi

exec apache2-foreground
