<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// جدولة مهمة إغلاق المحلات تلقائياً في نهاية اليوم
Schedule::command('queue:prune-failed')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
