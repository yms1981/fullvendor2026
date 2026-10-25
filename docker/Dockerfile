FROM php:7.4-apache AS fullvendor-base

RUN a2enmod rewrite

# Debian 11 (Bullseye) dejó el soporte LTS; los paquetes viven en archive.debian.org.
RUN if [ -f /etc/apt/sources.list ]; then \
      sed -i 's/deb.debian.org/archive.debian.org/g; s/security.debian.org/archive.debian.org/g' /etc/apt/sources.list; \
    fi \
 && if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
      sed -i 's/deb.debian.org/archive.debian.org/g; s/security.debian.org/archive.debian.org/g' /etc/apt/sources.list.d/debian.sources; \
    fi \
 && printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid-until

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libcurl4-openssl-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    curl \
    intl \
    opcache \
    pdo \
    soap \
    xml \
    gettext \
    zip

RUN curl -sS https://getcomposer.org/installer | php \
  &&  mv composer.phar /usr/bin/composer \
    && composer --version

RUN apt-get update && apt-get install -y libmemcached11 libmemcachedutil2 build-essential libmemcached-dev libz-dev
RUN pecl install memcached
RUN echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini

RUN echo "upload_max_filesize=100M\npost_max_size=100M\nmemory_limit=256M" > /usr/local/etc/php/conf.d/uploads.ini

RUN pecl install redis &&  docker-php-ext-enable redis


# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Imagen producción MVC (PHP 7.4 + Apache, sin xdebug). Build: --target prod
FROM fullvendor-base AS prod

FROM fullvendor-base AS dev
# install xdebug
RUN yes | pecl install xdebug-2.9.0
RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
        && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini \
        && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.remote_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM dev AS fullvendor-ssl-asivas
COPY certificates/asivas-fullchain.pem /etc/ssl/certs/ssl-cert-snakeoil.pem
COPY certificates/asivas-privkey.pem /etc/ssl/private/ssl-cert-snakeoil.key

RUN a2enmod ssl && a2ensite default-ssl

FROM fullvendor-ssl-asivas AS with-api
RUN a2enmod allowmethods
COPY conf/api.conf /etc/apache2/conf-available/api.conf
RUN a2enmod proxy proxy_http && a2enconf api


