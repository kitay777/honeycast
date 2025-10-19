<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use App\Models\CastProfile;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        // ?date=YYYY-MM-DD を受ける（無ければ今日）
        $tz   = config('app.timezone', 'Asia/Tokyo');
        $day  = $request->query('date');
        $date = $day ? Carbon::createFromFormat('Y-m-d', $day, $tz)->startOfDay()
                     : Carbon::today($tz);

        // 画面上部のカレンダーチップ（今日から7日分）
        $days = collect(range(0, 6))->map(function($i) use ($date) {
            $d = $date->copy()->startOfDay()->addDays($i - 0); // 先頭を選択日基準にしてもOK
            return [
                'date'  => $d->toDateString(),
                'label' => $d->isoFormat('MM/DD（dd）'),
                'is_today' => $d->isToday(),
                
            ];
        })->values();

        // 選択日のシフト取得（cast_shifts テーブル）
        // テーブル: id, cast_profile_id, date, start_time, end_time, is_reserved
        $rows = DB::table('cast_shifts')
            ->join('cast_profiles', 'cast_profiles.id', '=', 'cast_shifts.cast_profile_id')
            ->whereDate('cast_shifts.date', $date->toDateString())
            ->orderBy('cast_shifts.start_time')
            ->orderBy('cast_profiles.nickname')
            ->select([
                'cast_shifts.id',
                'cast_shifts.cast_profile_id',
                'cast_shifts.date',
                'cast_shifts.start_time',
                'cast_shifts.end_time',
                'cast_shifts.is_reserved',
                'cast_profiles.nickname',
                'cast_profiles.photo_path',
                'cast_profiles.is_blur_default',
            ])
            ->get();

        // 表示用にマッピング（24時跨ぎの表示もケア）
        $items = $rows->map(function($r) use ($tz) {
            // H:i 文字列へ
            $start = Carbon::createFromFormat('H:i:s', $r->start_time, $tz)->format('H:i');
            $end   = Carbon::createFromFormat('H:i:s', $r->end_time, $tz)->format('H:i');

            // 画面都合のカード用 shape
            return [
                'shift_id'   => (int)$r->id,
                'id'         => (int)$r->cast_profile_id,  // CastCard 用
                'nickname'   => $r->nickname,
                'photo_path' => $r->photo_path,
                'time_text'  => "{$start} - {$end}",
                'is_reserved'=> (bool)$r->is_reserved,
                // CastCard 互換フィールド
                'is_blur_default'          => (bool)$r->is_blur_default,
                'viewer_has_unblur_access' => false,
                'should_blur'              => false,
                'liked'                    => false,
            ];
        });

        return Inertia::render('Shifts/Index', [
            'selected_date' => $date->toDateString(),
            'days'          => $days,
            'items'         => $items,
            'today'         => \Illuminate\Support\Carbon::today($tz)->toDateString(),
        ]);
    }
}
