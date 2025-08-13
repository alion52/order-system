FROM php:8.2-apache

# Установка зависимостей с очисткой кеша в одном RUN слое
RUN apt-get update && \
    apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    && pecl install \
    redis \
    && docker-php-ext-enable \
    redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка композера
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем только нужные файлы
COPY . /var/www/html/

# Настройка прав и оптимизация
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    a2enmod rewrite

# Рабочая директория
WORKDIR /var/www/html

# Порт и команда запуска
EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]