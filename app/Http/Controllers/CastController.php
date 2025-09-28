<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use App\Models\CastShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // ★ 追加
use Inertia\Inertia;

class CastController extends Controller
{

public function show(CastProfile $cast)
{
    /* ▼▼ ここを必ず最初に置く：直近1週間のスケジュール生成 ▼▼ */
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
    /* ▲▲ ここまで ― $days を必ず定義 ▲▲ */

    $viewer = Auth::user();

    // プロフ許可は使わず、本人/管理者のみ無条件アンブラー
    $isOwnerOrAdmin = $viewer && (
        $cast->user_id === $viewer->id ||
        (method_exists($viewer, 'isAdmin') && $viewer->isAdmin())
    );
    $isBlurDefault = is_null($cast->is_blur_default) ? true : (bool)$cast->is_blur_default;

    // 写真ごとのぼかし判定（個別申請のみで解除）
    $photos = $cast->photos()->orderBy('sort_order')->get()->map(function ($p) use ($viewer, $isOwnerOrAdmin, $isBlurDefault) {
        $photoDefault   = is_null($p->is_blur_default) ? true : (bool)$p->is_blur_default;
        $photoHasAccess = $isOwnerOrAdmin || $p->viewerHasUnblurAccess($viewer); // ※プロフ許可は足さない
        $photoPerm      = $p->permissionFor($viewer);
       // ★ マスター（primary）は常に非ブラー
       if ($p->is_primary) {
           $should = false;
       } else {
           $should = ($isBlurDefault || $photoDefault) && !$photoHasAccess;
       }

        return [
            'id'           => $p->id,
            'url'          => Storage::disk('public')->url($p->path),
            'is_primary'   => (bool)$p->is_primary,
            'should_blur'  => $should,
            'unblur'       => [
                'requested' => (bool) ($photoPerm && $photoPerm->status === 'pending'),
                'status'    => $photoPerm->status ?? null,
            ],
        ];
    });

    // タグ配列化
    $tags = $cast->tags;
    if (!is_array($tags)) {
        $tags = collect(preg_split('/[,\s、，]+/u', (string)$tags, -1, PREG_SPLIT_NO_EMPTY))
            ->values()->all();
    }

    return Inertia::render('Cast/Show', [
        'cast' => [
            'id'         => $cast->id,
            'nickname'   => $cast->nickname,
            'photo_path' => $cast->photo_path,
            'rank'       => $cast->rank,
            'age'        => $cast->age,
            'height_cm'  => $cast->height_cm,
            'cup'        => $cast->cup,
            'style'      => $cast->style,
            'alcohol'    => $cast->alcohol,
            'mbti'       => $cast->mbti,
            'area'       => $cast->area,
            'tags'       => $tags,
            'freeword'   => $cast->freeword,
            'user_id'    => $cast->user_id,

            // フォールバック用
            'is_blur_default'       => $isBlurDefault,
            'viewer_is_owner_admin' => $isOwnerOrAdmin,
            'should_blur' => false,

            // サブ写真（個別申請のみで解除）
            'photos' => $photos,
        ],
        'schedule' => $days,
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
                ->map(fn ($s) => [
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
