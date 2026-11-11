#!/usr/bin/env bash
set -e

cd /var/www/html

export PORT="${PORT:-8080}"

mkdir -p \
    storage/app/public/ofertas \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

php artisan storage:link --force || true
chown -R www-data:www-data storage bootstrap/cache

echo "Esperando conexión a MySQL y aplicando migraciones..."
for i in $(seq 1 15); do
    if php artisan migrate --force; then
        break
    fi
    if [ "$i" -eq 15 ]; then
        echo "Error: no se pudo migrar la base de datos." >&2
        exit 1
    fi
    sleep 2
done

# Sembrar categorías y cuentas iniciales solo si la base aún no tiene usuarios
if [ "$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Models\User::count();')" = "0" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
