<?php

namespace App\Services;

use App\Models\QueueEntry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    // إرسال رسالة الانضمام للطابور
    public function sendJoinMessage(QueueEntry $entry): void
    {
        $barber = $entry->barber;
        $waitMinutes = $barber->estimatedWaitMinutes($entry->position);

        $message = "مرحباً {$entry->customer_name}،\n\n" .
            "✅ انضممت بنجاح لطابور *{$barber->shop_name}*\n\n" .
            "📍 رقمك في الطابور: *{$entry->position}*\n" .
            "⏱️ الوقت التقريبي للانتظار: *{$waitMinutes} دقيقة*\n\n" .
            "سنُرسل لك رسالة عندما يقترب دورك 🔔";

        $this->send($entry->customer_whatsapp, $message);
    }

    // إرسال رسالة دور الزبون
    public function sendTurnMessage(QueueEntry $entry): void
    {
        $barber = $entry->barber;
        $confirmLink = config('app.frontend_url') . "/queue/{$entry->id}/confirm";

        $message = "🎉 دورك قادم في *{$barber->shop_name}*!\n\n" .
            "يرجى التأكيد خلال *10 دقائق* وإلا سيُلغى مكانك.\n\n" .
            "✅ تأكيد الحضور: {$confirmLink}";

        $this->send($entry->customer_whatsapp, $message);
    }

    // إرسال رسالة الإلغاء
    public function sendCancellationMessage(QueueEntry $entry): void
    {
        $barber = $entry->barber;
        $shopLink = config('app.frontend_url') . "/shop/{$barber->id}";

        $message = "❌ انتهت مهلة التأكيد وتم إلغاء مكانك في *{$barber->shop_name}*.\n\n" .
            "يمكنك إعادة الانضمام في أي وقت:\n{$shopLink}";

        $this->send($entry->customer_whatsapp, $message);
    }

    // دالة الإرسال الفعلية
    private function send(string $phone, string $message): void
    {
        // تنظيف رقم الهاتف
        $phone = $this->formatPhone($phone);

        // محاولة الإرسال عبر Twilio أولاً
        if (config('services.twilio.sid')) {
            $this->sendViaTwilio($phone, $message);
            return;
        }

        // استخدام CallMeBot كبديل مجاني
        if (config('services.callmebot.enabled')) {
            $this->sendViaCallMeBot($phone, $message);
            return;
        }

        // تسجيل الرسالة فقط في حالة عدم وجود خدمة
        Log::info("WhatsApp [محاكاة] إلى {$phone}: {$message}");
    }

    // إرسال عبر Twilio
    private function sendViaTwilio(string $phone, string $message): void
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.from');

            Http::withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => "whatsapp:{$phone}",
                    'Body' => $message,
                ]);
        } catch (\Exception $e) {
            Log::error("خطأ Twilio: " . $e->getMessage());
        }
    }

    // إرسال عبر CallMeBot (مجاني)
    private function sendViaCallMeBot(string $phone, string $message): void
    {
        try {
            $apiKey = env('CALLMEBOT_API_KEY');
            if (!$apiKey) {
                Log::info("CallMeBot: مفتاح API غير مضبوط، محاكاة الإرسال إلى {$phone}");
                return;
            }

            Http::get(config('services.callmebot.api_url'), [
                'phone' => $phone,
                'text' => $message,
                'apikey' => $apiKey,
            ]);
        } catch (\Exception $e) {
            Log::error("خطأ CallMeBot: " . $e->getMessage());
        }
    }

    // تنسيق رقم الهاتف
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '05')) {
                $phone = '+966' . substr($phone, 1);
            } elseif (str_starts_with($phone, '5')) {
                $phone = '+966' . $phone;
            }
        }

        return $phone;
    }
}
