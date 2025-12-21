#!/bin/bash
set -e

# Get the MySQL pod name
POD_NAME=$(kubectl get pods -l app=mysql -o jsonpath="{.items[0].metadata.name}")

if [ -z "$POD_NAME" ]; then
    echo "❌ MySQL pod not found!"
    exit 1
fi

echo "📦 Found MySQL pod: $POD_NAME"

# Check if file exists
if [ ! -f "k8s/dev.sql" ]; then
    echo "❌ File k8s/dev.sql not found!"
    exit 1
fi

echo "🚀 Copying dev.sql to container (this may take a while)..."
kubectl cp k8s/dev.sql "$POD_NAME":/tmp/dev.sql

echo "🔄 Importing database (truncating existing data)..."
# We add "DROP DATABASE IF EXISTS dev; CREATE DATABASE dev; USE dev;" before the import
kubectl exec "$POD_NAME" -- /bin/bash -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS dev; CREATE DATABASE dev; USE dev; SOURCE /tmp/dev.sql;"'

echo "✅ Import completed!"
