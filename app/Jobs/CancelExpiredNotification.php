<?php

namespace App\Jobs;

use App\Models\QueueEntry;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelExpiredNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $entryId
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $entry = QueueEntry::with('barber')->find($this->entryId);

        // إذا لم يعد موجوداً أو غير في حالة notified فلا نفعل شيئاً
        if (!$entry || $entry->status !== QueueEntry::STATUS_NOTIFIED) {
            return;
        }

        // إلغاء المكان تلقائياً
        $entry->update(['status' => QueueEntry::STATUS_CANCELLED]);

        // إشعار الزبون بالإلغاء
        $whatsApp->sendCancellationMessage($entry);

        // إشعار التالي في الطابور
        $this->notifyNextInQueue($entry->barber_id, $whatsApp);
    }

    private function notifyNextInQueue(int $barberId, WhatsAppService $whatsApp): void
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

            $whatsApp->sendTurnMessage($next);

            // جدولة إلغاء تلقائي بعد 10 دقائق إذا لم يؤكد
            CancelExpiredNotification::dispatch($next->id)->delay(now()->addMinutes(10));
        }
    }
}
