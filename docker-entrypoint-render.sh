#!/usr/bin/env bash
set -Eeuo pipefail

DATA_DIR="/var/lib/mysql"
INIT_MARKER="${DATA_DIR}/.campusresolve_initialized"

if [[ ! -d "${DATA_DIR}/mysql" ]]; then
  mkdir -p "${DATA_DIR}"
  chown -R mysql:mysql "${DATA_DIR}"
  mariadb-install-db --user=mysql --datadir="${DATA_DIR}" >/tmp/mariadb-install.log 2>&1
fi

mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld
mariadbd --user=mysql --datadir="${DATA_DIR}" --socket=/run/mysqld/mysqld.sock --pid-file=/run/mysqld/mysqld.pid --bind-address=127.0.0.1 --skip-name-resolve --console >/tmp/mariadb.log 2>&1 &
DB_PID=$!
cleanup() {
  kill "${DB_PID}" 2>/dev/null || true
}
trap cleanup EXIT TERM INT

for attempt in {1..60}; do
  if mariadb-admin ping --user=root --protocol=socket --socket=/run/mysqld/mysqld.sock >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

if ! mariadb-admin ping --user=root --protocol=socket --socket=/run/mysqld/mysqld.sock >/dev/null 2>&1; then
  cat /tmp/mariadb.log >&2 || true
  exit 1
fi

if [[ ! -f "${INIT_MARKER}" ]]; then
  mariadb --user=root --protocol=socket --socket=/run/mysqld/mysqld.sock < /var/www/html/database.sql
  touch "${INIT_MARKER}"
fi

PORT="${PORT:-10000}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s#<VirtualHost \*:80>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
