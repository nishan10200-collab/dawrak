# دورك (Dawrak) - تطبيق إدارة طابور الحلاق

## هيكل المشروع

```
dawrak/
├── app/                          # كود Laravel الرئيسي
│   ├── Http/Controllers/
│   │   ├── Admin/               # المدير: AuthController, BarberController
│   │   ├── Barber/              # الحلاق: AuthController, QueueController, ServiceController, StatsController, QrController
│   │   └── Customer/            # الزبون: ShopController, QueueStatusController
│   ├── Jobs/
│   │   └── CancelExpiredNotification.php  # إلغاء تلقائي بعد 10 دقائق
│   ├── Middleware/
│   │   ├── AdminAuthenticate.php
│   │   └── BarberAuthenticate.php
│   ├── Models/
│   │   ├── Admin.php
│   │   ├── Barber.php
│   │   ├── Service.php
│   │   ├── QueueEntry.php
│   │   └── DailyStat.php
│   └── Services/
│       └── WhatsAppService.php   # Twilio أو CallMeBot
├── database/
│   ├── migrations/              # 7 migrations
│   └── seeders/                 # Admin + 2 حلاقين تجريبيين
├── routes/
│   └── api.php                  # كل API routes
├── frontend/                    # Vue 3 + Vite
│   └── src/
│       ├── views/
│       │   ├── admin/           # Login, Dashboard
│       │   ├── barber/          # Login, Dashboard, Services, QRCode
│       │   └── customer/        # ShopPage, QueueStatus
│       ├── stores/              # Pinia: admin.js, barber.js
│       ├── router/              # Vue Router
│       └── api/axios.js         # Axios مع interceptors
└── docker/                      # nginx + supervisord + start.sh
```

## بيانات الاختبار

| النوع | البريد | كلمة المرور |
|-------|--------|-------------|
| مدير | admin@dawrak.com | admin123 |
| حلاق 1 | barber1@dawrak.com | barber123 |
| حلاق 2 | barber2@dawrak.com | barber123 |

## المسارات الرئيسية

- `/admin` → لوحة المدير
- `/barber` → لوحة الحلاق
- `/shop/{barber_id}` → صفحة الزبون
- `/queue/{entry_id}` → حالة الطابور

## منطق الطابور

1. زبون ينضم → يأخذ position = آخر position + 1
2. حلاق يضغط "انتهيت" → done + إشعار التالي
3. التالي يُشعَر → 10 دقائق للتأكيد
4. إذا لم يؤكد → cancelled تلقائياً + إشعار التالي

## متغيرات البيئة المهمة

```
APP_URL=https://your-app.railway.app
FRONTEND_URL=https://your-app.railway.app
DB_*=قاعدة بيانات Railway MySQL
QUEUE_CONNECTION=database
CALLMEBOT_ENABLED=true
CALLMEBOT_API_KEY=your_key  # للواتساب المجاني
```
