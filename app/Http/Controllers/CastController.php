<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use App\Models\CastShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CastController extends Controller
{
    public function show(CastProfile $cast)
    {

        // 直近1週間の予定を表示用に成形
        $today = Carbon::today();
        $days = collect(range(0, 6))->map(function ($i) use ($cast, $today) {
            $d = $today->copy()->addDays($i)->toDateString();
            $slots = $cast->shifts()->whereDate('date', $d)
                ->orderBy('start_time')
                ->get(['start_time', 'end_time'])
                ->map(fn($s) => [
                    'start' => substr($s->start_time, 0, 5),
                    'end'   => substr($s->end_time, 0, 5),
                ]);

            return [
                'date'    => $d,
                'weekday' => $today->copy()->addDays($i)->locale('ja')->isoFormat('ddd'),
                'slots'   => $slots,
            ];
        });

        // ====== ブラー判定付与 ======
        $viewer = Auth::user();
        // モデル側に viewerHasUnblurAccess(User|null $viewer): bool がある前提（前ターンで定義済み）
        $viewerHasAccess = $cast->viewerHasUnblurAccess($viewer);
        $isBlurDefault   = is_null($cast->is_blur_default) ? true : (bool)$cast->is_blur_default;
        $shouldBlur      = $isBlurDefault && !$viewerHasAccess;     // 最終的にブラーを掛けるか?

        // 閲覧者の申請状態（unblur 用）
        $perm = $cast->permissionFor($viewer); // null 可
        $unblur = [
            'requested' => (bool) ($perm && $perm->status === 'pending'),
            'status'    => $perm->status ?? null, // null|pending|approved|denied
        ];

        // tags が文字列なら配列化（カンマ/空白区切り）
        $tags = $cast->tags;
        if (!is_array($tags)) {
            $tags = collect(preg_split('/[,\s、，]+/u', (string) $tags, -1, PREG_SPLIT_NO_EMPTY))
                ->values()->all();
        }
        return Inertia::render('Cast/Show', [
            'cast' => [
                'id'                       => $cast->id,
                'nickname'                 => $cast->nickname,
                'photo_path'               => $cast->photo_path,
                'rank'                     => $cast->rank,
                'age'                      => $cast->age,
                'height_cm'                => $cast->height_cm,
                'cup'                      => $cast->cup,
                'style'                    => $cast->style,
                'alcohol'                  => $cast->alcohol,
                'mbti'                     => $cast->mbti,
                'area'                     => $cast->area,
                'tags'                     => $tags,
                'freeword'                 => $cast->freeword,
                'user_id'                  => $cast->user_id,

                // ★ ブラー関連をフロントへ
                'is_blur_default'          => $isBlurDefault,
                'viewer_has_unblur_access' => $viewerHasAccess,
                'should_blur'              => $shouldBlur,
            ],
            'schedule' => $days,
            // ★ 申請UI用
            'unblur'   => $unblur,
        ]);
    }

    /** 編集画面 */
    public function editSchedule(CastProfile $cast)
    {
        $this->authorizeEditing($cast);

        $today = Carbon::today();
        $days = collect(range(0, 6))->map(function ($i) use ($cast, $today) {
            $d = $today->copy()->addDays($i)->toDateString();
            $slots = $cast->shifts()->whereDate('date', $d)
                ->orderBy('start_time')
                ->get(['start_time', 'end_time'])
                ->map(fn($s) => [
                    'start' => substr($s->start_time, 0, 5),
                    'end'   => substr($s->end_time, 0, 5),
                ]);
            return ['date' => $d, 'slots' => $slots];
        });

        return Inertia::render('Cast/ScheduleEdit', [
            'castId' => $cast->id,
            'days'   => $days,
        ]);
    }

    /** 保存（1週間ぶんをまとめて上書き） */
    public function updateSchedule(Request $request, CastProfile $cast)
    {
        $this->authorizeEditing($cast);

        $data = $request->validate([
            'days'                     => ['required', 'array', 'size:7'],
            'days.*.date'              => ['required', 'date'],
            'days.*.slots'             => ['array'],
            'days.*.slots.*.start'     => ['required_with:days.*.slots', 'regex:/^\d{2}:\d{2}$/'],
            'days.*.slots.*.end'       => ['required_with:days.*.slots', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        DB::transaction(function () use ($cast, $data) {
            $dates = collect($data['days'])->pluck('date')->all();

            // 対象日の既存枠を一旦削除して入れ直し（単純で安全）
            CastShift::where('cast_profile_id', $cast->id)
                ->whereIn('date', $dates)
                ->delete();

            $rows = [];
            foreach ($data['days'] as $d) {
                foreach ($d['slots'] ?? [] as $s) {
                    $rows[] = [
                        'cast_profile_id' => $cast->id,
                        'date'       => $d['date'],
                        'start_time' => $s['start'] . ':00',
                        'end_time'   => $s['end']   . ':00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if ($rows) CastShift::insert($rows);
        });

        return back()->with('success', 'スケジュールを保存しました。');
    }

    /** 自分のキャストプロファイルだけ編集可（必要ならGate/Policy化もOK） */
    private function authorizeEditing(CastProfile $cast): void
    {
        if (Auth::id() !== $cast->user_id) {
            abort(403, 'You cannot edit this schedule.');
        }
    }
}
