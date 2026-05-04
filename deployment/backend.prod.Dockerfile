# Stage 2: PHP Application
FROM php:8.4-fpm

# Install dependencies, minimal for PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    zlib1g-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip opcache pcntl bcmath sockets

# PHP Timeouts
RUN echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/docker-php-timeouts.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/docker-php-timeouts.ini \
    && sed -i 's/pm.max_children = 5/pm.max_children = 20/g' /usr/local/etc/php-fpm.d/www.conf \
    && echo "request_terminate_timeout = 300" >> /usr/local/etc/php-fpm.d/www.conf

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer without depending on a separate Docker Hub stage.
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /var/www

# Copy only composer files first for caching
COPY backend/composer.json backend/composer.lock* /var/www/

# Install dependencies (no dev, no scripts, no autoloader yet)
# Skip audit blocks and increase timeout for slow networks
RUN composer config process-timeout 600 \
    && composer install --no-interaction --prefer-dist --no-dev --no-scripts --no-autoloader

# Now copy the rest of the application code
COPY backend/ /var/www

# Finalize autoloader and run scripts
RUN rm -f bootstrap/cache/*.php && composer dump-autoload --optimize --no-dev

# Set permissions early for artisan commands
RUN chown -R www-data:www-data /var/www

# Warm up package discovery and optimization (no DB needed for these)
RUN php artisan package:discover --ansi \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Keep a copy of public for entrypoint
RUN cp -a /var/www/public /var/www/public.from-image

COPY backend/scripts/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
