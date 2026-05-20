<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Barber;
use App\Http\Controllers\Customer;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات API - تطبيق دورك
|--------------------------------------------------------------------------
*/

// ===== مسارات المدير =====
Route::prefix('admin')->group(function () {
    Route::post('login', [Admin\AuthController::class, 'login']);

    Route::middleware('admin.auth')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout']);
        Route::get('barbers', [Admin\BarberController::class, 'index']);
        Route::post('barbers', [Admin\BarberController::class, 'store']);
        Route::put('barbers/{barber}', [Admin\BarberController::class, 'update']);
        Route::put('barbers/{barber}/subscription', [Admin\BarberController::class, 'updateSubscription']);
        Route::delete('barbers/{barber}', [Admin\BarberController::class, 'destroy']);
        Route::get('stats', [Admin\BarberController::class, 'stats']);
    });
});

// ===== مسارات الحلاق =====
Route::prefix('barber')->group(function () {
    Route::post('login', [Barber\AuthController::class, 'login']);

    Route::middleware('barber.auth')->group(function () {
        Route::get('me', [Barber\AuthController::class, 'me']);
        Route::post('logout', [Barber\AuthController::class, 'logout']);

        // الطابور
        Route::get('queue', [Barber\QueueController::class, 'index']);
        Route::put('status', [Barber\QueueController::class, 'toggleStatus']);
        Route::put('queue/{entry}/done', [Barber\QueueController::class, 'markDone']);
        Route::put('queue/{entry}/no-show', [Barber\QueueController::class, 'markNoShow']);

        // الخدمات
        Route::get('services', [Barber\ServiceController::class, 'index']);
        Route::post('services', [Barber\ServiceController::class, 'store']);
        Route::put('services/{service}', [Barber\ServiceController::class, 'update']);
        Route::delete('services/{service}', [Barber\ServiceController::class, 'destroy']);

        // الإحصائيات
        Route::get('stats', [Barber\StatsController::class, 'index']);

        // QR Code
        Route::get('qr', [Barber\QrController::class, 'generate']);
    });
});

// ===== مسارات الزبائن (عامة) =====
Route::prefix('shop')->group(function () {
    Route::get('{barber}', [Customer\ShopController::class, 'show']);
    Route::post('{barber}/join', [Customer\ShopController::class, 'join'])
        ->middleware('throttle:queue-join');
});

Route::prefix('queue')->group(function () {
    Route::get('{entry}/status', [Customer\QueueStatusController::class, 'status']);
    Route::post('{entry}/confirm', [Customer\QueueStatusController::class, 'confirm']);
    Route::delete('{entry}', [Customer\QueueStatusController::class, 'cancel']);
});
