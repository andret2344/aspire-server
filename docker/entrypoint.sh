#!/usr/bin/env sh
set -eu

#echo "Waiting for database (and creating it if missing)..."
#READY=0
#for _ in $(seq 1 60); do
#  if php bin/console doctrine:database:create --if-not-exists >/dev/null 2>&1; then
#    READY=1
#    break
#  fi
#  sleep 1
#done
#
#[ "$READY" -eq 1 ] || { echo "DB not ready"; exit 1; }

echo "Creating database..."
php bin/console doctrine:database:create --if-not-exists

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming up cache..."
php bin/console cache:warmup

exec "$@"
