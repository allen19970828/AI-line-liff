FROM php:8.5-fpm-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    nginx \
    nodejs \
    npm \
    postgresql-client \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
    pdo_pgsql \
    pdo_mysql \
    bcmath \
    opcache \
    pcntl \
    zip \
    gd \
    intl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 【宗師修正 1 & 2】：建立 Nginx 執行目錄，並將 Nginx 陣營強制轉為 www-data
RUN mkdir -p /etc/nginx/http.d /run/nginx && \
    sed -i 's/user nginx;/user www-data;/g' /etc/nginx/nginx.conf && \
    cat <<'EOF' > /etc/nginx/http.d/default.conf
server {
    listen 8080;
    root /var/www/public;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php index.html;
    charset utf-8;

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }

    error_page 404 /index.php;

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

# Configure Supervisor for process management
RUN mkdir -p /etc/supervisor/conf.d && \
    cat <<'EOF' > /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/dev/stdout
logfile_maxbytes=0

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
EOF

# Copy application files
COPY . /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www && \
    mkdir -p /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www/bootstrap/cache

# Configure git for composer
RUN git config --global --add safe.directory /var/www

# Install dependencies (Production)
RUN composer install --optimize-autoloader --no-dev
RUN if [ -f package.json ]; then npm install; fi

# Build frontend assets
RUN if [ -f package.json ] && grep -q "\"build\"" package.json; then npm run build; fi

# 【宗師修正 3】：拔除這裡的 php artisan optimize，避免快取到空的環境變數！

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
