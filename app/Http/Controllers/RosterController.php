<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RosterController extends Controller
{
    /**
     * 在籍一覧：
     * - 並び順：次の出勤(next_at)が近い順 → 予定なし(NULL)は最後
     * - 各キャストの今後の出勤を最大5件表示（今日の現在時刻以降を含む）
     * - 簡易検索 ?q=（ニックネーム）
     * - いいね状態 liked（任意／ログイン時）
     */
    public function index(Request $request)
    {
        $tz   = config('app.timezone', 'Asia/Tokyo');
        $now  = Carbon::now($tz);
        $today = $now->toDateString();
        $nowTime = $now->format('H:i:s');

        $kw = trim((string)$request->query('q', ''));

        // --- 次の出勤(next_at) 抽出用サブクエリ -----------------------------
        // 対象：今日以降（今日なら現在時刻以降）の最も早い開始日時
        $nextShiftSub = DB::table('cast_shifts as cs')
            ->selectRaw(
                'cs.cast_profile_id,
                 MIN(
                    CASE
                      WHEN cs.date > ? THEN TIMESTAMP(cs.date, cs.start_time)
                      WHEN cs.date = ? AND cs.start_time >= ? THEN TIMESTAMP(cs.date, cs.start_time)
                      ELSE NULL
                    END
                 ) as next_at',
                [$today, $today, $nowTime]
            )
            ->groupBy('cs.cast_profile_id');

        // --- 在籍一覧（cast_profiles）に next_at を結合 -----------------------
        $q = DB::table('cast_profiles as cp')
            ->leftJoinSub($nextShiftSub, 'ns', 'ns.cast_profile_id', '=', 'cp.id')
            ->select([
                'cp.id','cp.nickname','cp.photo_path','cp.is_blur_default',
                'ns.next_at',
            ])
            // 並び：次の出勤がある人→近い順／無い人は最後
            ->orderByRaw('CASE WHEN ns.next_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('ns.next_at', 'asc');

        if ($kw !== '') {
            $q->where(function ($w) use ($kw) {
                $w->where('cp.nickname', 'like', "%{$kw}%");
            });
        }

        // ページネーション（必要に応じて件数は調整）
        $casts = $q->paginate(24)->withQueryString();

        // --- いいね（任意）：ログイン中ユーザーがLikeしたかを付与 ----------
        $viewer = $request->user();
        $likedIds = $viewer
            ? DB::table('cast_likes')->where('user_id', $viewer->id)->pluck('cast_profile_id')->all()
            : [];

        // --- 表示ページに含まれる cast_id だけ、今後のシフト最大5件を取得 -----
        $castIds = $casts->pluck('id')->all();
        $upcomingByCast = [];

        if (!empty($castIds)) {
            // 未来シフト（今日の現在時刻以降含む）にROW_NUMBERで連番を付与
            $base = DB::table('cast_shifts')
                ->select([
                    'cast_profile_id',
                    'date',
                    'start_time',
                    'end_time',
                    DB::raw('ROW_NUMBER() OVER (PARTITION BY cast_profile_id ORDER BY date, start_time) AS rn'),
                ])
                ->whereIn('cast_profile_id', $castIds)
                ->where(function ($w) use ($today, $nowTime) {
                    $w->where('date', '>', $today)
                      ->orWhere(function ($ww) use ($today, $nowTime) {
                          $ww->where('date', '=', $today)
                             ->where('start_time', '>=', $nowTime);
                      });
                });

            // rn <= 5 までに限定
            $upcomingRows = DB::query()->fromSub($base, 'u')
                ->where('rn', '<=', 5)
                ->orderBy('cast_profile_id')->orderBy('date')->orderBy('start_time')
                ->get();

            // cast_id => [{label, date, start, end}, ...]
            $upcomingByCast = $upcomingRows->groupBy('cast_profile_id')->map(function ($rows) use ($tz) {
                return $rows->map(function ($r) use ($tz) {
                    $d  = Carbon::createFromFormat('Y-m-d', $r->date, $tz);
                    $st = Carbon::createFromFormat('H:i:s', $r->start_time, $tz)->format('H:i');
                    $et = Carbon::createFromFormat('H:i:s', $r->end_time,   $tz)->format('H:i');
                    return [
                        'date'  => $r->date,
                        'start' => $st,
                        'end'   => $et,
                        'label' => $d->isoFormat('M/D（dd） ') . $st . ' - ' . $et,
                    ];
                })->values()->all();
            })->toArray();
        }

        // --- フロント向け shape（CastCard互換 + next_at + upcoming_shifts） ---
        $items = $casts->through(function ($row) use ($tz, $upcomingByCast, $likedIds) {
            $nextAtStr = $row->next_at ? Carbon::parse($row->next_at, $tz)->format('Y/m/d H:i') : null;

            return [
                'id'         => (int) $row->id,
                'nickname'   => $row->nickname,
                'photo_path' => $row->photo_path,
                'is_blur_default'          => (bool) $row->is_blur_default,
                'viewer_has_unblur_access' => false,
                'should_blur'              => false,
                'liked'      => in_array($row->id, $likedIds, true),

                // 次回出勤（バッジ表示用）
                'next_at'    => $nextAtStr,
                'next_badge' => $nextAtStr
                    ? Carbon::parse($row->next_at, $tz)->isoFormat('M/D（dd） H:mm')
                    : '予定なし',

                // 今後の出勤 最大5件（label: "M/D(曜) HH:MM - HH:MM"）
                'upcoming_shifts' => $upcomingByCast[$row->id] ?? [],
            ];
        });

        return Inertia::render('Roster/Index', [
            'items'   => $items,
            'filters' => ['q' => $kw],
            'today'   => $today,
        ]);
    }
}
