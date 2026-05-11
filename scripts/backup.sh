#!/usr/bin/env bash
# Backup PostgreSQL + MinIO from the docker-compose stack.
#
#   ./scripts/backup.sh [DEST_DIR]      # default DEST_DIR = ./backups
#
# Cron example (daily 03:15; keep the repo + this script on the host):
#   15 3 * * *  cd /opt/cmbcoreseller && ./scripts/backup.sh /var/backups/cmb >> /var/log/cmb-backup.log 2>&1
#
# Off-site: ship $DEST_DIR to a different region/provider afterwards (rclone / aws s3 sync / …).
# Retention target (docs/07-infra/observability-and-backup.md §5): 7 daily + 4 weekly + a few monthly.
# Restore: ./scripts/restore.sh <pg-dump.sql.gz>
set -euo pipefail

DEST_DIR="${1:-./backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
COMPOSE="docker compose"

mkdir -p "$DEST_DIR"
ABS_DEST="$(cd "$DEST_DIR" && pwd)"

echo "[backup $STAMP] PostgreSQL -> $DEST_DIR/pg-$STAMP.sql.gz"
$COMPOSE exec -T postgres pg_dump -U cmbcoreseller -d cmbcoreseller --clean --if-exists \
  | gzip > "$DEST_DIR/pg-$STAMP.sql.gz"

echo "[backup $STAMP] MinIO bucket 'omnisell' -> $DEST_DIR/minio-$STAMP/"
$COMPOSE run --rm --no-deps --entrypoint sh -v "$ABS_DEST:/backup" minio-init -c "
  mc alias set local http://minio:9000 omnisell omnisell-secret &&
  mc mirror --overwrite --remove local/omnisell \"/backup/minio-$STAMP\"
"

# Prune local copies older than 14 days (weekly/monthly retention lives off-site).
find "$DEST_DIR" -maxdepth 1 -name 'pg-*.sql.gz' -mtime +14 -delete 2>/dev/null || true
find "$DEST_DIR" -maxdepth 1 -type d -name 'minio-*' -mtime +14 -exec rm -rf {} + 2>/dev/null || true

echo "[backup $STAMP] done. Now ship $DEST_DIR off-site."
