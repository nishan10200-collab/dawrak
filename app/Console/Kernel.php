<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // تنظيف الـ tokens القديمة يومياً
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
        // تنظيف الـ failed jobs يومياً
        $schedule->command('queue:prune-failed')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
