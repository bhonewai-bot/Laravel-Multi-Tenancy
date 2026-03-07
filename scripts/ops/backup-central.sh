#!/usr/bin/env bash
set -euo pipefail

ROOT_PASS="${DB_PASSWORD:-root}"
OUT_DIR="${1:-storage/app/backups}"
STAMP="$(date +%F_%H%M%S)"
mkdir -p "$OUT_DIR"

docker compose exec -T mysql mysqldump -uroot -p"${ROOT_PASS}" \
  --single-transaction --routines --events central > "${OUT_DIR}/central_${STAMP}.sql"

echo "central backup: ${OUT_DIR}/central_${STAMP}.sql"
