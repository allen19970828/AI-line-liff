#!/bin/sh
set -e

echo "Running production entrypoint..."

# 1. 確保持久化目錄存在且可讀寫 (這是在 Run-time 處理掛載空間)
if [ -d "/data" ]; then
    echo "Ensuring /data/public permissions..."
    mkdir -p /data/public
    chown -R www-data:www-data /data/public
    chmod -R 775 /data/public
fi

# 2. Laravel 優化 (在執行階段跑，確保能讀到環境變數)
php artisan optimize

echo "Running self-healing routine..."
# 執行自癒腳本 (自動處理 migration、建立 storage link 等)
php artisan app:self-healing --force

echo "Starting services..."

# Start PHP-FPM in background
php-fpm -D

# Start NGINX in foreground
nginx -g 'daemon off;'
