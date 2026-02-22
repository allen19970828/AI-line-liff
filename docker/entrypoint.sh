#!/bin/sh
set -e

echo "Running initial setup for Laravel..."

# Laravel optimization (快取設定與路由，提升效能)
php artisan optimize

echo "Running self-healing routine..."
# 執行自癒腳本 (自動處理 migration、建立 storage link 等初始化動作)
# 加入 --force 因為在 production 環境需要強制執行 migration
php artisan app:self-healing --force

echo "Starting services..."

# Start PHP-FPM in background
php-fpm -D

# Start NGINX in foreground (這是容器的主行程)
nginx -g 'daemon off;'
