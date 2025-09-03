FROM php:7.4-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git unzip \
    libonig-dev libxml2-dev libzip-dev libicu-dev g++ make \
    curl nano vim mariadb-client \
    && docker-php-ext-install -j$(nproc) bcmath intl mbstring pdo pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP settings
RUN { \
    echo "memory_limit=512M"; \
    echo "upload_max_filesize=64M"; \
    echo "post_max_size=64M"; \
    echo "max_execution_time=120"; \
  } > /usr/local/etc/php/conf.d/zz-custom.ini

WORKDIR /var/www/html

# Permissions for Laravel
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

CMD ["php-fpm"]

