#!/usr/bin/env sh
set -eu

echo "Creating database..."
php bin/console doctrine:database:create --if-not-exists

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming up cache..."
php bin/console cache:warmup

exec "$@"
