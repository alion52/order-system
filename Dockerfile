FROM php:8.2-apache

# 1. Установка зависимостей
RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Установка PHP расширений
RUN docker-php-ext-install pdo pdo_pgsql sockets && \
    pecl install redis && \
    docker-php-ext-enable redis

# 3. Настройка PHP
RUN echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini && \
    echo "extension=sockets.so" > /usr/local/etc/php/conf.d/sockets.ini

# 4. Копирование файлов
COPY . /var/www/html/

# 5. Настройка базовых прав
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# 6. Запуск
WORKDIR /var/www/html
EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]