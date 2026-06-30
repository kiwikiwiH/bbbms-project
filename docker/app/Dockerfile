FROM php:8.4-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libzip-dev \
        openssl \
    && docker-php-ext-install pdo_mysql zip opcache \
    && a2enmod rewrite \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/app/tarrlok/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /app

COPY blockchain/ ./blockchain/

COPY tarrlok/composer.json tarrlok/composer.lock ./tarrlok/
RUN cd tarrlok && composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

COPY tarrlok/app ./tarrlok/app
COPY tarrlok/bootstrap/app.php tarrlok/bootstrap/providers.php ./tarrlok/bootstrap/
COPY tarrlok/config ./tarrlok/config
COPY tarrlok/database ./tarrlok/database
COPY tarrlok/public ./tarrlok/public
COPY tarrlok/resources ./tarrlok/resources
COPY tarrlok/routes ./tarrlok/routes
COPY tarrlok/artisan ./tarrlok/artisan

RUN mkdir -p tarrlok/storage/framework/{cache,sessions,views} tarrlok/storage/logs tarrlok/bootstrap/cache \
    && cd tarrlok && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/app/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
