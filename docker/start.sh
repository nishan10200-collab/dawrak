#!/bin/sh
set -e

echo "🚀 بدء تشغيل تطبيق دورك..."

# الانتظار حتى تكون قاعدة البيانات جاهزة
echo "⏳ انتظار قاعدة البيانات..."
until php artisan migrate:status > /dev/null 2>&1; do
    sleep 2
done

# توليد مفتاح التطبيق إذا لم يكن موجوداً
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# تشغيل الـ migrations
echo "🗄️ تشغيل الـ migrations..."
php artisan migrate --force

# تشغيل الـ seeders في المرة الأولى فقط
if php artisan db:table admins --count 2>/dev/null | grep -q "0"; then
    echo "🌱 تشغيل البيانات التجريبية..."
    php artisan db:seed --force
fi

# إنشاء رابط الـ storage
php artisan storage:link --force 2>/dev/null || true

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache

# إنشاء مجلد supervisor logs
mkdir -p /var/log/supervisor

echo "✅ التطبيق جاهز!"

# تشغيل supervisor
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
