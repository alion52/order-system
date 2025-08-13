FROM php:8.2-apache

# Установка зависимостей для PostgreSQL
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Копируем файлы приложения
COPY . /var/www/html/

# Настройка прав
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]