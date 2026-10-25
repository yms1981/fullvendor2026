FROM ghcr.io/fullvendor/php:7.4-apache-dev

ARG APP_VERSION
ENV APP_VERSION=${APP_VERSION}

COPY docker/conf/api.conf /etc/apache2/conf-available/api.conf
RUN sed -i -e 's/laravel.test/api/g' /etc/apache2/conf-available/api.conf


COPY ./mvc /var/www/html

# Traducciones JSON para getLangJsonSection() en el panel company
RUN mkdir -p /var/www/html/api
COPY ./api/lang /var/www/html/api/lang

# uploads excluido por .dockerignore: crear carpeta escribible por Apache (logo/favicon admin)
RUN mkdir -p /var/www/html/uploads/logos \
	&& chown -R www-data:www-data /var/www/html/uploads \
	&& chmod -R 0777 /var/www/html/uploads

RUN sed -i "s/define('APP_VERSION',\s*'[^']*')/define('APP_VERSION', '${APP_VERSION}-test')/" /var/www/html/application/config/constants.php

RUN composer install
