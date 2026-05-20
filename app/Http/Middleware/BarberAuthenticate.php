<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BarberAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من توكن الحلاق
        $barber = $request->user('barber');

        if (!$barber) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول كحلاق للوصول لهذه الصفحة',
            ], 401);
        }

        // التحقق من حالة الاشتراك
        if (!$barber->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'اشتراكك منتهي، يرجى التواصل مع الإدارة لتجديده',
            ], 403);
        }

        return $next($request);
    }
}
