#!/bin/sh
set -e

php artisan migrate --force

# Seed servers if table is empty
COUNT=$(php artisan tinker --no-interaction --execute="echo App\Models\Server::count();")
if [ "$COUNT" = "0" ]; then
    php artisan db:seed --class=ServerSeeder --force
fi

exec php-fpm
