<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\CallRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index()
    {
        // 予約（reservations）
        $resRows = Reservation::with(['castProfile:id,nickname'])
            ->where('user_id', Auth::id())
            ->orderByDesc('date')->orderBy('start_time')
            ->get();

        // 呼ぶ（call_requests）
        $callRows = CallRequest::where('user_id', Auth::id())
            ->orderByDesc('date')->orderBy('start_time')
            ->get();

        // 共通の表示用配列に正規化してマージ
        $list = collect();

        foreach ($resRows as $r) {
            $list->push([
                'id'          => $r->id,
                'type'        => 'reservation',
                'date'        => optional($r->date)->format('Y-m-d'),
                'date_str'    => optional($r->date)->locale('ja')->isoFormat('YYYY年 M月D日 (ddd)'),
                'start_time'  => substr($r->start_time, 0, 5),
                'end_time'    => substr($r->end_time,   0, 5),
                'cast_count'  => $r->cast_count ?? 1,
                'guest_count' => $r->guest_count ?? 1,
                'nomination'  => $r->castProfile?->nickname, // 指名名として表示
                'place'       => $r->place ?? '-',
                'set_plan'    => $r->set_plan ?? null,
                'status'      => $r->status,
                'show_url'    => "/reserve/{$r->id}",        // 予約の詳細（用意していれば）
            ]);
        }

        foreach ($callRows as $c) {
            // CallRequest::class で date を casts していない場合をケア
            $dateStr = method_exists($c->date, 'toDateString')
                ? $c->date->toDateString()
                : (string)$c->date;

            $list->push([
                'id'          => $c->id,
                'type'        => 'call',
                'date'        => $dateStr,
                'date_str'    => \Carbon\Carbon::parse($dateStr)->locale('ja')->isoFormat('YYYY年 M月D日 (ddd)'),
                'start_time'  => substr($c->start_time, 0, 5),
                'end_time'    => substr($c->end_time,   0, 5),
                'cast_count'  => (int)$c->cast_count,
                'guest_count' => (int)$c->guest_count,
                'nomination'  => $c->nomination,
                'place'       => $c->place,
                'set_plan'    => $c->set_plan,
                'status'      => $c->status,
                'show_url'    => "/call/{$c->id}",
            ]);
        }

        // 日付 + 開始時刻で降順
        $list = $list->sortByDesc(fn($x) => ($x['date'] ?? '') . ' ' . ($x['start_time'] ?? ''))
                     ->values();

        return Inertia::render('Reservation/Index', ['list' => $list]);
    }
}
