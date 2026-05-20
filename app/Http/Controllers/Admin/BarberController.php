<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BarberController extends Controller
{
    // قائمة الحلاقين
    public function index()
    {
        $barbers = Barber::withCount([
            'queueEntries as today_queue_count' => function ($q) {
                $q->whereDate('date', today())
                  ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving']);
            },
            'dailyStats as total_served' => function ($q) {
                $q->selectRaw('sum(customers_served)');
            },
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $barbers->map(fn($b) => $this->formatBarber($b)),
        ]);
    }

    // إضافة حلاق جديد
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'shop_name' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'required|email|unique:barbers',
            'password' => 'required|string|min:6',
            'subscription_status' => 'in:active,inactive,trial',
            'subscription_expires_at' => 'nullable|date',
            'avg_service_minutes' => 'integer|min:5|max:120',
            'address' => 'nullable|string',
        ]);

        $data['password'] = Hash::make($data['password']);
        $barber = Barber::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الحلاق بنجاح',
            'data' => $this->formatBarber($barber),
        ], 201);
    }

    // تعديل حلاق
    public function update(Request $request, Barber $barber)
    {
        $data = $request->validate([
            'name' => 'string|max:100',
            'shop_name' => 'string|max:150',
            'phone' => 'string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'email|unique:barbers,email,' . $barber->id,
            'password' => 'nullable|string|min:6',
            'avg_service_minutes' => 'integer|min:5|max:120',
            'address' => 'nullable|string',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $barber->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الحلاق بنجاح',
            'data' => $this->formatBarber($barber->fresh()),
        ]);
    }

    // تحديث الاشتراك
    public function updateSubscription(Request $request, Barber $barber)
    {
        $data = $request->validate([
            'subscription_status' => 'required|in:active,inactive,trial',
            'subscription_expires_at' => 'nullable|date',
        ]);

        $barber->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الاشتراك بنجاح',
            'data' => $this->formatBarber($barber->fresh()),
        ]);
    }

    // حذف حلاق
    public function destroy(Barber $barber)
    {
        $barber->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحلاق بنجاح',
        ]);
    }

    // إحصائيات عامة
    public function stats()
    {
        $stats = [
            'total_barbers' => Barber::count(),
            'active_barbers' => Barber::where('subscription_status', 'active')->count(),
            'trial_barbers' => Barber::where('subscription_status', 'trial')->count(),
            'open_barbers' => Barber::where('is_open', true)->count(),
            'today_customers' => \App\Models\QueueEntry::whereDate('date', today())->count(),
            'today_served' => \App\Models\QueueEntry::whereDate('date', today())
                ->where('status', 'done')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // تنسيق بيانات الحلاق
    private function formatBarber(Barber $barber): array
    {
        return [
            'id' => $barber->id,
            'name' => $barber->name,
            'shop_name' => $barber->shop_name,
            'phone' => $barber->phone,
            'whatsapp' => $barber->whatsapp,
            'email' => $barber->email,
            'subscription_status' => $barber->subscription_status,
            'subscription_expires_at' => $barber->subscription_expires_at?->format('Y-m-d'),
            'is_open' => $barber->is_open,
            'avg_service_minutes' => $barber->avg_service_minutes,
            'address' => $barber->address,
            'logo' => $barber->logo ? asset('storage/' . $barber->logo) : null,
            'has_active_subscription' => $barber->hasActiveSubscription(),
            'created_at' => $barber->created_at->format('Y-m-d'),
        ];
    }
}
