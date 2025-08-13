FROM php:8.2-apache

# Установка системных зависимостей
RUN apt-get update && \
    apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    sockets && \  # Важно для работы Redis
    pecl install \
    redis \
    && docker-php-ext-enable \
    redis \
    opcache

# Настройка PHP
RUN echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini && \
    echo "extension=sockets.so" > /usr/local/etc/php/conf.d/sockets.ini

# Копирование файлов приложения
COPY . /var/www/html/

# Настройка прав
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage

# Рабочая директория
WORKDIR /var/www/html

EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]