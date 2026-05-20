# ===== مرحلة 1: بناء الـ Frontend =====
FROM node:20-alpine AS frontend-builder

WORKDIR /frontend

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

# ===== مرحلة 2: بناء التطبيق الرئيسي =====
FROM php:8.2-fpm-alpine AS base

# تثبيت الامتدادات المطلوبة
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql gd zip opcache

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# نسخ ملفات المشروع
COPY . .

# نسخ الـ frontend المبني
COPY --from=frontend-builder /frontend/../public/app /var/www/html/public/app

# تثبيت اعتماديات PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# إنشاء ملف .env من المثال
RUN cp .env.example .env

# ضبط صلاحيات المجلدات
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# إعداد nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# إنشاء مجلد logs لـ nginx
RUN mkdir -p /var/log/nginx /run/nginx

EXPOSE 80

# سكريبت التشغيل
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
