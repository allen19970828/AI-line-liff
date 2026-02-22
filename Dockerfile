# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.3
FROM docker.io/library/php:${PHP_VERSION}-fpm

# 加入環境變數，告知 Laravel 我們的環境
ENV APP_ENV=production
ENV APP_DEBUG=false
WORKDIR /var/www

# 安裝依賴 (包含 nginx, 資料庫工具等)
RUN set -eux \
    && apt update \
    && apt install -y cron curl gettext git grep libicu-dev nginx pkg-config unzip \
    && rm -rf /var/lib/apt/lists/*

# 安裝 PHP 擴展 (透過官方推薦的腳本)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync
# 確保包含 redis 與 pdo_pgsql (PostgreSQL 驅動)
RUN install-php-extensions @composer apcu bcmath gd intl mysqli opcache pcntl pdo_mysql pdo_pgsql sysvsem zip redis

# 複製應用程式原始碼
COPY . /var/www

# 安裝 Composer 依賴
RUN composer install --optimize-autoloader --classmap-authoritative --no-dev

# 安裝 npm 依賴和構建前端資源 (如果有)
RUN if [ -f package.json ]; then npm install && npm run build; fi

# Laravel 優化 (將設定檔等快取起來)
RUN php artisan optimize

# 配置 Nginx (將 /storage 硬連接回 /data/public，確保重啟不消失)
RUN cat > /etc/nginx/sites-enabled/default << 'EOF'
server {
listen 8080;
root /var/www/public;

add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";

index index.php index.html;
charset utf-8;

location = /favicon.ico { access_log off; log_not_found off; }
location = /robots.txt  { access_log off; log_not_found off; }

error_page 404 /index.php;

# 處理 PHP 請求
location ~ \.php$ {
try_files $uri =404;
fastcgi_split_path_info ^(.+\.php)(/.*)$;
fastcgi_pass 127.0.0.1:9000;
fastcgi_index index.php;
include fastcgi_params;
fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
fastcgi_param DOCUMENT_ROOT $realpath_root;
fastcgi_param PATH_INFO $fastcgi_path_info;
fastcgi_hide_header X-Powered-By;
}

# [關鍵修復]：儲存空間路由直接 alias 到持久化磁碟 /data/public
location /storage {
alias /data/public;
expires 30d;
add_header Cache-Control "public, immutable";
}

location / {
try_files $uri $uri/ /index.php$is_args$args;
gzip_static on;
}

location ~ /\.(?!well-known).* {
deny all;
}

error_log /dev/stderr;
access_log /dev/stderr;
}
EOF

# 佈置入口腳本 (Entrypoint)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 設定權限讓 www-data 可以讀寫
RUN chown -R www-data:www-data /var/www
RUN chown -R www-data:www-data /data/public || true

# Zeabur 期望的對外 Port
EXPOSE 8080

# 使用腳本啟動
ENTRYPOINT ["entrypoint.sh"]
