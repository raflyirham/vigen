FROM php:8.2-fpm

WORKDIR /app

# Install system deps + Node + FFmpeg + Nginx
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip nodejs npm ffmpeg nginx \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Install & build frontend (React via Vite)
RUN npm install && npm run build

# Laravel optimizations
RUN php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

# Runtime entrypoint that maps Nginx to $PORT and serves Laravel public/
RUN printf '#!/bin/sh\n\
set -e\n\
\n\
PORT="${PORT:-8080}"\n\
cat > /etc/nginx/conf.d/default.conf <<EOF\n\
server {\n\
    listen ${PORT};\n\
    server_name _;\n\
    root /app/public;\n\
    index index.php index.html;\n\
\n\
    location / {\n\
        try_files \$uri \$uri/ /index.php?\$query_string;\n\
    }\n\
\n\
    location ~ \\.php$ {\n\
        include fastcgi_params;\n\
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
    }\n\
}\n\
EOF\n\
\n\
php-fpm -D\n\
exec nginx -g "daemon off;"\n' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 8080

# Production runtime: PHP-FPM + Nginx (instead of artisan serve)
CMD ["/usr/local/bin/start.sh"]
