<?php

namespace App\Http\Controllers\Barber;

use App\Http\Controllers\Controller;
use App\Models\DailyStat;
use App\Models\QueueEntry;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    // إحصائيات الحلاق
    public function index(Request $request)
    {
        $barber = $request->user('barber');

        $todayStats = [
            'customers_served' => QueueEntry::where('barber_id', $barber->id)
                ->whereDate('date', today())
                ->where('status', 'done')
                ->count(),
            'customers_waiting' => QueueEntry::where('barber_id', $barber->id)
                ->whereDate('date', today())
                ->whereIn('status', ['waiting', 'notified', 'confirmed', 'serving'])
                ->count(),
            'customers_cancelled' => QueueEntry::where('barber_id', $barber->id)
                ->whereDate('date', today())
                ->whereIn('status', ['cancelled', 'no_show'])
                ->count(),
        ];

        // إحصائيات الأسبوع الماضي
        $weeklyStats = DailyStat::where('barber_id', $barber->id)
            ->where('date', '>=', now()->subDays(7))
            ->orderBy('date')
            ->get()
            ->map(fn($s) => [
                'date' => $s->date->format('Y-m-d'),
                'customers_served' => $s->customers_served,
                'avg_wait_minutes' => $s->avg_wait_minutes,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $todayStats,
                'weekly' => $weeklyStats,
            ],
        ]);
    }
}
