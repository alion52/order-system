FROM php:8.2-apache

# Устанавливаем зависимости для PostgreSQL и Redis
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Копируем файлы проекта
COPY . /var/www/html/

# Настраиваем права
RUN chown -R www-data:www-data /var/www/html

# Порт для PHP-сервера
EXPOSE 10000

# Запускаем сервер
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]