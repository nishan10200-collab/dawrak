<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = [
        'barber_id',
        'customer_name',
        'customer_whatsapp',
        'service_id',
        'position',
        'status',
        'joined_at',
        'notified_at',
        'confirmed_at',
        'served_at',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'notified_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'served_at' => 'datetime',
            'date' => 'date',
            'position' => 'integer',
        ];
    }

    // الحالات المتاحة
    const STATUS_WAITING = 'waiting';
    const STATUS_NOTIFIED = 'notified';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_SERVING = 'serving';
    const STATUS_DONE = 'done';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    // علاقة الحلاق
    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    // علاقة الخدمة
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // التحقق إن كان الزبون نشطاً في الطابور
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_WAITING,
            self::STATUS_NOTIFIED,
            self::STATUS_CONFIRMED,
            self::STATUS_SERVING,
        ]);
    }

    // حساب وقت الانتظار المتبقي بالدقائق
    public function estimatedWaitMinutes(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        $activeAhead = QueueEntry::where('barber_id', $this->barber_id)
            ->whereDate('date', $this->date)
            ->whereIn('status', [self::STATUS_WAITING, self::STATUS_NOTIFIED, self::STATUS_CONFIRMED, self::STATUS_SERVING])
            ->where('position', '<', $this->position)
            ->count();

        return $activeAhead * ($this->barber->avg_service_minutes ?? 20);
    }
}
