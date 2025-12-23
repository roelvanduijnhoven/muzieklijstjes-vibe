#!/bin/bash
set -e

DUMP_FILE="local_dump.sql"

echo "📤 Exporting local database to $DUMP_FILE..."
# Export from local docker-compose mysql container
docker compose exec -T mysql mysqldump -u root -pjouwweb dev > "$DUMP_FILE"

if [ ! -s "$DUMP_FILE" ]; then
    echo "❌ Dump file is empty or failed to create!"
    rm -f "$DUMP_FILE"
    exit 1
fi
echo "✅ Export complete."

# Get the MySQL pod name
POD_NAME=$(kubectl get pods -l app=mysql -o jsonpath="{.items[0].metadata.name}")

if [ -z "$POD_NAME" ]; then
    echo "❌ MySQL pod not found!"
    exit 1
fi

echo "📦 Found MySQL pod: $POD_NAME"

echo "🚀 Copying $DUMP_FILE to container (this may take a while)..."
kubectl cp "$DUMP_FILE" "$POD_NAME":/tmp/dump.sql

echo "🔄 Importing database (truncating existing data)..."
# We add "DROP DATABASE IF EXISTS dev; CREATE DATABASE dev; USE dev;" before the import
kubectl exec "$POD_NAME" -- /bin/bash -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS dev; CREATE DATABASE dev; USE dev; SOURCE /tmp/dump.sql;"'

# Clean up local dump file
echo "🧹 Cleaning up..."
rm "$DUMP_FILE"

echo "✅ Import completed!"
