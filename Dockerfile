FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        libssl-dev \
        libzip-dev \
        git \
    && rm -rf /var/lib/apt/lists/*

# Установка и настройка PHP расширений
RUN docker-php-ext-install pdo pdo_pgsql sockets && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini && \
    echo "extension=sockets.so" > /usr/local/etc/php/conf.d/sockets.ini

# Копирование файлов приложения
COPY . /var/www/html/

# Настройка прав
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# Рабочая директория
WORKDIR /var/www/html

EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]