<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'barber_id',
        'category',
        'name',
        'description',
        'price',
        'image',
        'duration_minutes',
        'is_available',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'duration_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // علاقة الحلاق
    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    // الفئات المتاحة
    public static function categories(): array
    {
        return [
            'hair' => 'شعر',
            'beard' => 'ذقن',
            'special' => 'خاص',
            'offer' => 'عروض',
        ];
    }
}
