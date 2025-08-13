FROM php:8.2-apache

RUN apt-get update && \
    apt-get install -y libpq-dev redis-server && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html/

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]