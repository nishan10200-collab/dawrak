<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Barber extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'shop_name',
        'phone',
        'whatsapp',
        'email',
        'password',
        'subscription_status',
        'subscription_expires_at',
        'is_open',
        'avg_service_minutes',
        'address',
        'logo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_open' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'avg_service_minutes' => 'integer',
        ];
    }

    // علاقة الخدمات
    public function services()
    {
        return $this->hasMany(Service::class)->orderBy('sort_order');
    }

    // علاقة طابور الانتظار
    public function queueEntries()
    {
        return $this->hasMany(QueueEntry::class);
    }

    // طابور اليوم الحالي
    public function todayQueue()
    {
        return $this->hasMany(QueueEntry::class)
            ->whereDate('date', today())
            ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving'])
            ->orderBy('position');
    }

    // علاقة الإحصائيات اليومية
    public function dailyStats()
    {
        return $this->hasMany(DailyStat::class);
    }

    // التحقق من صلاحية الاشتراك
    public function hasActiveSubscription(): bool
    {
        if ($this->subscription_status === 'trial') {
            return true;
        }

        if ($this->subscription_status === 'active') {
            return $this->subscription_expires_at === null
                || $this->subscription_expires_at->isFuture();
        }

        return false;
    }

    // حساب الوقت التقريبي للانتظار
    public function estimatedWaitMinutes(int $position): int
    {
        return ($position - 1) * $this->avg_service_minutes;
    }
}
