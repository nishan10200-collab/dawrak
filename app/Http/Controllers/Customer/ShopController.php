<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\CancelExpiredNotification;
use App\Models\Barber;
use App\Models\QueueEntry;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp) {}

    // معلومات المحل + خدماته + حالة الطابور
    public function show(Barber $barber)
    {
        $services = $barber->services()
            ->where('is_available', true)
            ->get()
            ->groupBy('category')
            ->map(fn($items) => $items->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'price' => (float) $s->price,
                'duration_minutes' => $s->duration_minutes,
                'image' => $s->image ? asset('storage/' . $s->image) : null,
            ]));

        // حالة الطابور الحالي
        $queueCount = QueueEntry::where('barber_id', $barber->id)
            ->whereDate('date', today())
            ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving'])
            ->count();

        $estimatedWait = $queueCount * $barber->avg_service_minutes;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $barber->id,
                'name' => $barber->name,
                'shop_name' => $barber->shop_name,
                'phone' => $barber->phone,
                'address' => $barber->address,
                'logo' => $barber->logo ? asset('storage/' . $barber->logo) : null,
                'is_open' => $barber->is_open,
                'avg_service_minutes' => $barber->avg_service_minutes,
                'queue_count' => $queueCount,
                'estimated_wait_minutes' => $estimatedWait,
                'services' => $services,
            ],
        ]);
    }

    // الانضمام للطابور
    public function join(Request $request, Barber $barber)
    {
        // التحقق من أن المحل مفتوح
        if (!$barber->is_open) {
            return response()->json([
                'success' => false,
                'message' => 'المحل مغلق حالياً',
            ], 422);
        }

        // التحقق من صلاحية الاشتراك
        if (!$barber->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المحل غير متاح حالياً',
            ], 422);
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_whatsapp' => 'required|string|max:20',
            'service_id' => 'nullable|exists:services,id',
        ]);

        // التحقق من عدم تكرار الانضمام في نفس اليوم
        $existing = QueueEntry::where('barber_id', $barber->id)
            ->whereDate('date', today())
            ->where('customer_whatsapp', $data['customer_whatsapp'])
            ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'أنت موجود بالفعل في الطابور',
                'data' => ['entry_id' => $existing->id],
            ], 422);
        }

        // تحديد الموضع في الطابور
        $lastPosition = QueueEntry::where('barber_id', $barber->id)
            ->whereDate('date', today())
            ->max('position') ?? 0;

        $entry = QueueEntry::create([
            'barber_id' => $barber->id,
            'customer_name' => $data['customer_name'],
            'customer_whatsapp' => $data['customer_whatsapp'],
            'service_id' => $data['service_id'] ?? null,
            'position' => $lastPosition + 1,
            'status' => QueueEntry::STATUS_WAITING,
            'joined_at' => now(),
            'date' => today(),
        ]);

        // إرسال رسالة ترحيب
        $this->whatsApp->sendJoinMessage($entry->load('barber'));

        $estimatedWait = $barber->estimatedWaitMinutes($entry->position);

        return response()->json([
            'success' => true,
            'message' => 'تم الانضمام للطابور بنجاح',
            'data' => [
                'entry_id' => $entry->id,
                'position' => $entry->position,
                'estimated_wait_minutes' => $estimatedWait,
            ],
        ], 201);
    }
}
