#!/usr/bin/env bash
set -euo pipefail

TENANT_ID="${1:?usage: backup-tenant.sh <tenant_id> [out_dir]}"
ROOT_PASS="${DB_PASSWORD:-root}"
OUT_DIR="${2:-storage/app/backups}"
STAMP="$(date +%F_%H%M%S)"
TENANT_DB="tenant${TENANT_ID}"

mkdir -p "$OUT_DIR"

docker compose exec -T mysql mysqldump -uroot -p"${ROOT_PASS}" \
  --single-transaction --routines --events "${TENANT_DB}" > "${OUT_DIR}/${TENANT_DB}_${STAMP}.sql"

echo "tenant backup: ${OUT_DIR}/${TENANT_DB}_${STAMP}.sql"
