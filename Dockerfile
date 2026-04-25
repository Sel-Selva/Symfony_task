FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    libxml2-dev \
 && docker-php-ext-install pdo_mysql bcmath

RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /srv/app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . /srv/app

RUN composer install --no-interaction --optimize-autoloader

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
