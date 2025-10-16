<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        // 表示に使う文言や問い合わせ先（必要に応じて変更）
        return Inertia::render('Contact/Pending', [
            'supportEmail' => config('mail.from.address') ?? 'support@example.com',
            'message' => 'ただいまお問い合わせフォームを準備中です。近日中に公開いたします。'
        ]);
    }
}
