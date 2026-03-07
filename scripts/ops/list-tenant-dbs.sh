#!/usr/bin/env bash
set -euo pipefail

ROOT_PASS="${DB_PASSWORD:-root}"
docker compose exec -T mysql mysql -uroot -p"${ROOT_PASS}" -N -e \
  "SELECT CONCAT('tenant', id) AS tenant_db FROM central.tenants ORDER BY id;"
