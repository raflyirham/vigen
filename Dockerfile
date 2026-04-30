FROM php:8.2-cli

WORKDIR /app

# Install system deps + Node
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip nodejs npm \
    && docker-php-ext-install pdo pdo_mysql zip

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

# Expose port (Render injects $PORT)
CMD php artisan serve --host=0.0.0.0 --port=$PORT