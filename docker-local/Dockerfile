# Local development image for FreeScout + the fastn Integrations module.
FROM php:8.2-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
        libc-client-dev libkrb5-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libicu-dev libonig-dev libxml2-dev unzip cron \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli imap zip gd intl mbstring xml bcmath exif pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/freescout.conf \
    && a2enconf freescout
WORKDIR /var/www/html
