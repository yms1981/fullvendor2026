FROM ghcr.io/fullvendor/sail-8.3:base

ADD ./api/ /var/www/html/
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
RUN composer install

RUN yarn && yarn run build
