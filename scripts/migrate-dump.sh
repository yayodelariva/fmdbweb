#!/usr/bin/env bash
# Dump the local docker WordPress install (DB + uploads) into ./migration/
# so it can be uploaded to a hosting provider (e.g. Bluehost).
#
# Usage:  ./scripts/migrate-dump.sh [container-name]
#         Defaults to fmdbweb-wordpress-1.
#
# Output:
#   migration/fmdb-<timestamp>.sql       — full DB dump
#   migration/uploads-<timestamp>.tar.gz — wp-content/uploads
#   migration/MANIFEST-<timestamp>.txt   — sizes + source URL for the search-replace step
#
# After running, copy the two archives to the new host and follow the
# search-replace step (see todo_migrate_docker_to_host.md).

set -euo pipefail

CONTAINER="${1:-fmdbweb-wordpress-1}"
REPO_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
OUT_DIR="$REPO_ROOT/migration"
STAMP="$(date +%Y%m%d-%H%M%S)"
SQL_FILE="fmdb-$STAMP.sql"
UPLOADS_FILE="uploads-$STAMP.tar.gz"
MANIFEST="MANIFEST-$STAMP.txt"

WP="docker exec $CONTAINER php -d memory_limit=512M /usr/local/bin/wp"

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    echo "error: container '$CONTAINER' is not running." >&2
    echo "       start it with: docker compose up -d" >&2
    exit 1
fi

mkdir -p "$OUT_DIR"

echo "==> Dumping database from $CONTAINER"
$WP db export "/tmp/$SQL_FILE" --allow-root --add-drop-table
docker cp "$CONTAINER:/tmp/$SQL_FILE" "$OUT_DIR/$SQL_FILE"
docker exec "$CONTAINER" rm -f "/tmp/$SQL_FILE"

echo "==> Archiving wp-content/uploads"
if [ -d "$REPO_ROOT/wp-content/uploads" ]; then
    tar -czf "$OUT_DIR/$UPLOADS_FILE" -C "$REPO_ROOT/wp-content" uploads
else
    echo "    (no wp-content/uploads on host — pulling from container instead)"
    docker exec "$CONTAINER" tar -czf "/tmp/$UPLOADS_FILE" -C /var/www/html/wp-content uploads
    docker cp "$CONTAINER:/tmp/$UPLOADS_FILE" "$OUT_DIR/$UPLOADS_FILE"
    docker exec "$CONTAINER" rm -f "/tmp/$UPLOADS_FILE"
fi

SOURCE_URL="$( $WP option get siteurl --allow-root 2>/dev/null | tr -d '\r' )"

{
    echo "FMDB migration bundle"
    echo "Created: $(date -Iseconds)"
    echo "Container: $CONTAINER"
    echo "Source siteurl: $SOURCE_URL"
    echo
    echo "Files:"
    ls -lh "$OUT_DIR/$SQL_FILE" "$OUT_DIR/$UPLOADS_FILE" | awk '{print "  " $5 "\t" $9}'
    echo
    echo "Next steps on the new host:"
    echo "  1. Import the SQL into the new WP database (phpMyAdmin or 'mysql -u USER -p DB < $SQL_FILE')."
    echo "  2. Extract uploads into wp-content/ (so paths land at wp-content/uploads/...)."
    echo "  3. Run search-replace from '$SOURCE_URL' to the new https URL:"
    echo "       wp search-replace '$SOURCE_URL' 'https://NEW-DOMAIN' --all-tables --skip-columns=guid"
    echo "  4. Resave permalinks in Settings -> Permalinks."
} > "$OUT_DIR/$MANIFEST"

echo
echo "Done. Bundle in $OUT_DIR:"
ls -lh "$OUT_DIR/$SQL_FILE" "$OUT_DIR/$UPLOADS_FILE" "$OUT_DIR/$MANIFEST"
