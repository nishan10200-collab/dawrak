<?php

namespace App\Http\Controllers\Barber;

use App\Http\Controllers\Controller;
use App\Jobs\CancelExpiredNotification;
use App\Models\DailyStat;
use App\Models\QueueEntry;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp) {}

    // قائمة انتظار اليوم
    public function index(Request $request)
    {
        $barber = $request->user('barber');

        $queue = QueueEntry::with('service')
            ->where('barber_id', $barber->id)
            ->whereDate('date', today())
            ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving'])
            ->orderBy('position')
            ->get();

        $todayServed = QueueEntry::where('barber_id', $barber->id)
            ->whereDate('date', today())
            ->where('status', 'done')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'queue' => $queue->map(fn($e) => $this->formatEntry($e)),
                'today_served' => $todayServed,
                'is_open' => $barber->is_open,
            ],
        ]);
    }

    // تغيير حالة المحل (فتح/إغلاق)
    public function toggleStatus(Request $request)
    {
        $barber = $request->user('barber');

        $request->validate([
            'is_open' => 'required|boolean',
        ]);

        $barber->update(['is_open' => $request->is_open]);

        return response()->json([
            'success' => true,
            'message' => $request->is_open ? 'تم فتح المحل بنجاح' : 'تم إغلاق المحل بنجاح',
            'data' => ['is_open' => $barber->is_open],
        ]);
    }

    // انتهى من زبون (done)
    public function markDone(Request $request, QueueEntry $entry)
    {
        $barber = $request->user('barber');

        if ($entry->barber_id !== $barber->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if (!in_array($entry->status, ['serving', 'confirmed', 'notified', 'waiting'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير حالة هذا الزبون',
            ], 422);
        }

        $entry->update([
            'status' => QueueEntry::STATUS_DONE,
            'served_at' => now(),
        ]);

        // تحديث الإحصائيات اليومية
        $this->updateDailyStats($barber->id);

        // إشعار التالي في الطابور
        $this->notifyNext($barber->id);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل انتهاء الخدمة',
        ]);
    }

    // الزبون غائب (no_show)
    public function markNoShow(Request $request, QueueEntry $entry)
    {
        $barber = $request->user('barber');

        if ($entry->barber_id !== $barber->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $entry->update(['status' => QueueEntry::STATUS_NO_SHOW]);

        // إشعار التالي
        $this->notifyNext($barber->id);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل غياب الزبون',
        ]);
    }

    // إشعار التالي في الطابور
    private function notifyNext(int $barberId): void
    {
        $next = QueueEntry::where('barber_id', $barberId)
            ->whereDate('date', today())
            ->where('status', QueueEntry::STATUS_WAITING)
            ->orderBy('position')
            ->first();

        if ($next) {
            $next->update([
                'status' => QueueEntry::STATUS_NOTIFIED,
                'notified_at' => now(),
            ]);

            $this->whatsApp->sendTurnMessage($next);

            // جدولة إلغاء تلقائي بعد 10 دقائق
            CancelExpiredNotification::dispatch($next->id)->delay(now()->addMinutes(10));
        }
    }

    // تحديث الإحصائيات اليومية
    private function updateDailyStats(int $barberId): void
    {
        $served = QueueEntry::where('barber_id', $barberId)
            ->whereDate('date', today())
            ->where('status', 'done')
            ->count();

        DailyStat::updateOrCreate(
            ['barber_id' => $barberId, 'date' => today()],
            ['customers_served' => $served]
        );
    }

    // تنسيق بيانات الزبون
    private function formatEntry(QueueEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'customer_name' => $entry->customer_name,
            'customer_whatsapp' => $entry->customer_whatsapp,
            'position' => $entry->position,
            'status' => $entry->status,
            'service' => $entry->service ? [
                'id' => $entry->service->id,
                'name' => $entry->service->name,
                'price' => $entry->service->price,
                'duration_minutes' => $entry->service->duration_minutes,
            ] : null,
            'joined_at' => $entry->joined_at?->format('H:i'),
            'notified_at' => $entry->notified_at?->format('H:i'),
            'wait_minutes' => $entry->estimatedWaitMinutes(),
        ];
    }
}
