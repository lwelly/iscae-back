FROM php:8.4-cli

WORKDIR /var/www

# تثبيت dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ المشروع
COPY . .

# إصلاح Laravel dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# فتح المنفذ
EXPOSE 10000

# تشغيل السيرفر
CMD php artisan serve --host=0.0.0.0 --port=10000