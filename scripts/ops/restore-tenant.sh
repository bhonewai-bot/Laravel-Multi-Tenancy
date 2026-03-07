#!/usr/bin/env bash
set -euo pipefail

TENANT_ID="${1:?usage: restore-tenant.sh <tenant_id> <dump_file.sql>}"
DUMP_FILE="${2:?usage: restore-tenant.sh <tenant_id> <dump_file.sql>}"
ROOT_PASS="${DB_PASSWORD:-root}"
TENANT_DB="tenant${TENANT_ID}"

test -f "${DUMP_FILE}"

docker compose exec -T mysql mysql -uroot -p"${ROOT_PASS}" -e \
  "DROP DATABASE IF EXISTS \`${TENANT_DB}\`; CREATE DATABASE \`${TENANT_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

docker compose exec -T mysql mysql -uroot -p"${ROOT_PASS}" "${TENANT_DB}" < "${DUMP_FILE}"

echo "tenant restored: ${TENANT_DB} from ${DUMP_FILE}"
