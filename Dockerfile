FROM php:8.4-fpm

WORKDIR /var/www


RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    default-mysql-client \
    && docker-php-ext-install \
    pdo_mysql \
    bcmath \
    zip \
    && rm -rf /var/lib/apt/lists/*


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


COPY . /var/www


RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache


EXPOSE 9000


CMD ["php-fpm"]