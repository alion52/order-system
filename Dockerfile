# Используем официальный образ PHP с Apache
FROM php:8.2-apache

# Устанавливаем системные зависимости
RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        libzip-dev \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo pdo_pgsql zip && \
    pecl install redis && \
    docker-php-ext-enable redis

# Включаем модуль rewrite в Apache
RUN a2enmod rewrite

# Копируем файлы проекта
COPY . /var/www/html/

# Устанавливаем правильные права
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# Рабочая директория
WORKDIR /var/www/html

# Порт для Apache
EXPOSE 80

# Команда запуска
CMD ["apache2-foreground"]