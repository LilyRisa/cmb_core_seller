#!/usr/bin/env bash
# Restore from a backup produced by ./scripts/backup.sh.
#
#   ./scripts/restore.sh <pg-dump.sql.gz> [<minio-dir>]
#
# Order (see docs/07-infra/observability-and-backup.md §6): bring up postgres + minio,
# restore the Postgres dump (it includes DROP/CREATE), re-upload MinIO objects, then
# (re)start app/worker — Redis can stay empty (queue re-drives from webhook_events / cursors).
# DESTRUCTIVE: this overwrites the current database. Confirm before running in anything but DR.
set -euo pipefail

PG_DUMP="${1:?usage: restore.sh <pg-dump.sql.gz> [minio-dir]}"
MINIO_DIR="${2:-}"
COMPOSE="docker compose"

read -r -p "This will OVERWRITE the database in the running stack. Continue? [y/N] " ans
[ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "aborted."; exit 1; }

echo "[restore] ensuring postgres + minio are up..."
$COMPOSE up -d postgres minio
sleep 3

echo "[restore] restoring PostgreSQL from $PG_DUMP"
gunzip -c "$PG_DUMP" | $COMPOSE exec -T postgres psql -U cmbcoreseller -d cmbcoreseller

if [ -n "$MINIO_DIR" ]; then
  ABS_SRC="$(cd "$MINIO_DIR" && pwd)"
  echo "[restore] restoring MinIO bucket 'omnisell' from $MINIO_DIR"
  $COMPOSE run --rm --no-deps --entrypoint sh -v "$ABS_SRC:/restore:ro" minio-init -c "
    mc alias set local http://minio:9000 omnisell omnisell-secret &&
    mc mb --ignore-existing local/omnisell &&
    mc mirror --overwrite /restore local/omnisell
  "
fi

echo "[restore] restarting app/worker/scheduler..."
$COMPOSE up -d app worker scheduler web
echo "[restore] done. Smoke-test: curl http://localhost:8000/api/v1/health"
