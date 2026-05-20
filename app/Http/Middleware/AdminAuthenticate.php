<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من توكن المدير
        $user = $request->user('admin');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول كمدير للوصول لهذه الصفحة',
            ], 401);
        }

        return $next($request);
    }
}
