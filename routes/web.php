<?php

use Illuminate\Support\Facades\Route;

// إعادة توجيه جميع الطلبات لـ Vue SPA
Route::get('/{any}', function () {
    return file_exists(public_path('app/index.html'))
        ? response()->file(public_path('app/index.html'))
        : response('التطبيق قيد الإعداد...', 200);
})->where('any', '.*');
