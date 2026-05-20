<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyStat extends Model
{
    protected $fillable = [
        'barber_id',
        'date',
        'customers_served',
        'avg_wait_minutes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'customers_served' => 'integer',
            'avg_wait_minutes' => 'integer',
        ];
    }

    // علاقة الحلاق
    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }
}
