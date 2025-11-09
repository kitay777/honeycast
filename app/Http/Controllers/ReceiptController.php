<?php

namespace App\Http\Controllers;

use App\Models\CallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    /** 🔹 自分のリクエスト一覧 */
    public function index()
    {
        $requests = CallRequest::where('user_id', Auth::id())
            ->orderByDesc('id')
            ->get(['id', 'place', 'date', 'start_time', 'end_time', 'payment_method', 'final_price', 'executed_at', 'total_price', 'created_at']);

        return Inertia::render('Receipts/Index', [
            'requests' => $requests,
        ]);
    }

    /** 🔹 領収書ページ表示 */
    public function show(CallRequest $req)
    {
        // 自分のデータ以外は見れない
        abort_unless($req->user_id === Auth::id(), 403);

        // クレジット払いでなければ発行不可
        abort_unless($req->payment_method === 'credit', 403);

        // executed_at が無い場合は発行日時を記録（初回発行扱い）
        if (!$req->executed_at) {
            $req->update(['executed_at' => now()]);
        }

        return Inertia::render('Receipts/Show', [
            'req' => $req,
        ]);
    }
}
