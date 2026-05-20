<?php

namespace App\Http\Controllers\Barber;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    // توليد QR code لصفحة المحل
    public function generate(Request $request)
    {
        $barber = $request->user('barber');
        $shopUrl = config('app.frontend_url') . '/shop/' . $barber->id;

        // توليد QR code كـ SVG
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($shopUrl);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_svg' => base64_encode($qrCode),
                'shop_url' => $shopUrl,
            ],
        ]);
    }
}
