#!/bin/sh
set -e

schema="${DB_SCHEMA:-}"
schema_lower=$(printf '%s' "$schema" | tr '[:upper:]' '[:lower:]')

case "$schema_lower" in
    ''|public)
        echo 'DB_SCHEMA must name a non-public Educity PostgreSQL schema.' >&2
        exit 1
        ;;
    *[!A-Za-z0-9_]* )
        echo 'DB_SCHEMA may contain only letters, digits, and underscores.' >&2
        exit 1
        ;;
esac

if [ "${DB_CONNECTION:-}" != 'pgsql' ]; then
    echo 'DB_CONNECTION must be pgsql for the Educity production container.' >&2
    exit 1
fi

# The Artisan command repeats validation and uses Laravel's PostgreSQL PDO connection.
php artisan config:clear
php artisan educity:ensure-schema
php artisan migrate --force

# EducitySeeder uses non-destructive, idempotent inserts keyed by stable identifiers.
php artisan db:seed --class=EducitySeeder --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
