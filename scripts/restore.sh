#!/usr/bin/env bash
# Restore from a backup produced by ./scripts/backup.sh.
#
#   ./scripts/restore.sh <pg-dump.sql.gz> [<minio-dir>]
#   COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml" ./scripts/restore.sh ...
#
# Order (see docs/07-infra/observability-and-backup.md §6): bring up postgres + minio,
# restore the Postgres dump (it includes DROP/CREATE), re-upload MinIO objects, then
# (re)start app/worker — Redis can stay empty (queue re-drives from webhook_events / cursors).
# DESTRUCTIVE: this overwrites the current database. Confirm before running in anything but DR.
set -euo pipefail

PG_DUMP="${1:?usage: restore.sh <pg-dump.sql.gz> [minio-dir]}"
MINIO_DIR="${2:-}"
COMPOSE="${COMPOSE:-docker compose}"
PG_USER="${DB_USERNAME:-cmbcoreseller}"
PG_DB="${DB_DATABASE:-cmbcoreseller}"
S3_KEY="${AWS_ACCESS_KEY_ID:-omnisell}"
S3_SECRET="${AWS_SECRET_ACCESS_KEY:-omnisell-secret}"
S3_BUCKET="${AWS_BUCKET:-omnisell}"

read -r -p "This will OVERWRITE the database in the running stack. Continue? [y/N] " ans
[ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "aborted."; exit 1; }

echo "[restore] ensuring postgres + minio are up..."
$COMPOSE up -d postgres minio
sleep 3

echo "[restore] restoring PostgreSQL ($PG_DB) from $PG_DUMP"
gunzip -c "$PG_DUMP" | $COMPOSE exec -T postgres psql -U "$PG_USER" -d "$PG_DB"

if [ -n "$MINIO_DIR" ]; then
  ABS_SRC="$(cd "$MINIO_DIR" && pwd)"
  echo "[restore] restoring MinIO bucket '$S3_BUCKET' from $MINIO_DIR"
  $COMPOSE run --rm --no-deps --entrypoint sh -v "$ABS_SRC:/restore:ro" minio-init -c "
    mc alias set local http://minio:9000 '$S3_KEY' '$S3_SECRET' &&
    mc mb --ignore-existing local/'$S3_BUCKET' &&
    mc mirror --overwrite /restore local/'$S3_BUCKET'
  "
fi

echo "[restore] restarting app/worker/scheduler/web..."
$COMPOSE up -d app worker scheduler web
echo "[restore] done. Smoke-test: curl http://localhost:8000/api/v1/health (dev) or your domain (prod)."
