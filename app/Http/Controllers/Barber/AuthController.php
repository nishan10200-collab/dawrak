<?php

namespace App\Http\Controllers\Barber;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // تسجيل دخول الحلاق
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('barber')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            ], 401);
        }

        $barber = Auth::guard('barber')->user();
        $token = $barber->createToken('barber-token', ['barber'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'token' => $token,
                'barber' => $this->formatBarber($barber),
            ],
        ]);
    }

    // تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user('barber')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    // بيانات الحلاق الحالي
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatBarber($request->user('barber')),
        ]);
    }

    private function formatBarber($barber): array
    {
        return [
            'id' => $barber->id,
            'name' => $barber->name,
            'shop_name' => $barber->shop_name,
            'phone' => $barber->phone,
            'email' => $barber->email,
            'subscription_status' => $barber->subscription_status,
            'subscription_expires_at' => $barber->subscription_expires_at?->format('Y-m-d'),
            'is_open' => $barber->is_open,
            'avg_service_minutes' => $barber->avg_service_minutes,
            'address' => $barber->address,
            'logo' => $barber->logo ? asset('storage/' . $barber->logo) : null,
            'has_active_subscription' => $barber->hasActiveSubscription(),
        ];
    }
}
