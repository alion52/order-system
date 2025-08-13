FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        libzip-dev \
        libssl-dev \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP (ключевое изменение!)
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        sockets \
    && pecl install \
        redis \
    && docker-php-ext-enable \
        redis \
        opcache

# Явная настройка расширений
RUN echo "extension=pdo_pgsql.so" > /usr/local/etc/php/conf.d/pdo_pgsql.ini && \
    echo "extension=pgsql.so" > /usr/local/etc/php/conf.d/pgsql.ini && \
    echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini

# Копирование файлов приложения
COPY . /var/www/html/

# Настройка прав
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# Рабочая директория
WORKDIR /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]