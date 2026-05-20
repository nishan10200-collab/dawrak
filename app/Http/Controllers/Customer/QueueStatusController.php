<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\QueueEntry;
use Illuminate\Http\Request;

class QueueStatusController extends Controller
{
    // حالة الزبون في الطابور
    public function status(QueueEntry $entry)
    {
        $entry->load(['barber', 'service']);

        // حساب عدد من أمامه في الطابور
        $aheadCount = 0;
        if ($entry->isActive()) {
            $aheadCount = QueueEntry::where('barber_id', $entry->barber_id)
                ->whereDate('date', $entry->date)
                ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving'])
                ->where('position', '<', $entry->position)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $entry->id,
                'position' => $entry->position,
                'status' => $entry->status,
                'customer_name' => $entry->customer_name,
                'ahead_count' => $aheadCount,
                'estimated_wait_minutes' => $aheadCount * ($entry->barber->avg_service_minutes ?? 20),
                'shop_name' => $entry->barber->shop_name,
                'is_shop_open' => $entry->barber->is_open,
                'service' => $entry->service ? [
                    'name' => $entry->service->name,
                    'price' => $entry->service->price,
                ] : null,
                'joined_at' => $entry->joined_at?->format('H:i'),
                'notified_at' => $entry->notified_at?->format('H:i'),
            ],
        ]);
    }

    // تأكيد الوصول
    public function confirm(QueueEntry $entry)
    {
        if ($entry->status !== QueueEntry::STATUS_NOTIFIED) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التأكيد في هذه الحالة',
            ], 422);
        }

        $entry->update([
            'status' => QueueEntry::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد وصولك، يرجى الانتظار',
            'data' => ['status' => $entry->status],
        ]);
    }

    // إلغاء المكان
    public function cancel(QueueEntry $entry)
    {
        if (!$entry->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الطلب',
            ], 422);
        }

        $entry->update(['status' => QueueEntry::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء مكانك في الطابور',
        ]);
    }
}
