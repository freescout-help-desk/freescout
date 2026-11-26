#!/usr/bin/env bash
# Start FreeScout locally on http://localhost:8080 with podman (or docker).
# Usage: docker-local/run.sh            start (first run also installs FreeScout)
#        docker-local/run.sh stop       stop and remove the containers (data volume is kept)
set -euo pipefail
cd "$(dirname "$0")/.."
C=${CONTAINER_CLI:-$(command -v podman || command -v docker)}
NET=freescout-fastn
if [ "${1:-}" = "stop" ]; then
  $C rm -f freescout-app freescout-db >/dev/null 2>&1 || true
  exit 0
fi
$C network exists $NET 2>/dev/null || $C network create $NET >/dev/null 2>&1 || true
$C build -t freescout-fastn-local -f docker-local/Dockerfile docker-local
$C rm -f freescout-db freescout-app >/dev/null 2>&1 || true
$C run -d --name freescout-db --network $NET -v freescout-fastn-db:/var/lib/mysql \
  -e MARIADB_DATABASE=freescout -e MARIADB_USER=freescout -e MARIADB_PASSWORD=freescout -e MARIADB_ROOT_PASSWORD=freescout \
  docker.io/library/mariadb:10.11 >/dev/null
$C run -d --name freescout-app --network $NET -p 8080:80 -v "$PWD":/var/www/html freescout-fastn-local >/dev/null
echo "Waiting for the database..."
for i in $(seq 1 60); do
  $C exec freescout-db mariadb -ufreescout -pfreescout -e 'select 1' freescout >/dev/null 2>&1 && break
  sleep 2
done
if [ ! -f .env ]; then
  cat > .env <<ENV
APP_URL=http://localhost:8080
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=freescout-db
DB_PORT=3306
DB_DATABASE=freescout
DB_USERNAME=freescout
DB_PASSWORD=freescout
ENV
  $C exec freescout-app php artisan key:generate --force
  $C exec freescout-app php artisan migrate --force
  $C exec freescout-app php artisan freescout:create-user --role=admin --firstName=Admin --lastName=Local \
    --email=admin@example.com --password=admin12345 || true
fi
$C exec freescout-app chown -R www-data:www-data storage bootstrap/cache public/modules 2>/dev/null || true
$C exec freescout-app php artisan freescout:clear-cache >/dev/null 2>&1 || true
echo "FreeScout: http://localhost:8080  (admin@example.com / admin12345, local only)"
