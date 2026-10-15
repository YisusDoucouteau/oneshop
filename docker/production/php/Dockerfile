FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build


FROM php:8.4-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        default-mysql-client \
        libzip-dev \
        unzip \
    && docker-php-ext-install bcmath pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www
COPY --from=assets /app/public/build /var/www/public/build

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
    && cp -a /var/www/storage /var/www/storage-template \
    && chmod +x /var/www/docker/production/php/entrypoint.sh \
    && chown -R www-data:www-data /var/www/storage /var/www/storage-template /var/www/bootstrap/cache

ENTRYPOINT ["/var/www/docker/production/php/entrypoint.sh"]
CMD ["php-fpm"]
