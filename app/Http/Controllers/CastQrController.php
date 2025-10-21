<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CastQrController extends Controller
{
    public function show()
    {
        // キャスト登録ページのURL
        $registerUrl = url('/register/cast');

        // base64エンコードされたQRイメージを生成
        $qrBase64 = base64_encode(QrCode::format('png')->size(300)->margin(2)->generate($registerUrl));

        return Inertia::render('Cast/Qr', [
            'registerUrl' => $registerUrl,
            'qrBase64'    => 'data:image/png;base64,'.$qrBase64,
        ]);
    }
}
