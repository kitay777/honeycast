<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use App\Models\CastShift;
use App\Models\CastPhotoViewPermission;
use App\Models\CastPhoto;
use App\Models\Gift;
use App\Models\GiftSend;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CastController extends Controller
{
    /**
     * キャスト詳細（プロフィール＋写真＋直近1週間のスケジュール＋ギフト送付）
     *
     * ぼかしの仕様：
     *  - 初期状態は非ぼかし
     *  - DBの cast_photos.should_blur が true で、閲覧許可が無いときだけぼかす
     *  - primary は常に非ぼかし
     */
    public function show(CastProfile $cast)
    {
        // ===== 直近1週間スケジュール =====
        $cast->load('user');
        \Log::info('Cast debug', [
            'cast_id' => $cast->id,
            'user_id' => $cast->user_id,
            'user_exists' => !!$cast->user,
            'last_login_at' => $cast->user?->last_login_at,
        ]);
        $today = Carbon::today();
        $days  = collect(range(0, 6))->map(function ($i) use ($cast, $today) {
            $d = $today->copy()->addDays($i)->toDateString();
            $slots = $cast->shifts()
                ->whereDate('date', $d)
                ->orderBy('start_time')
                ->get(['start_time', 'end_time'])
                ->map(fn($s) => [
                    'start' => substr($s->start_time, 0, 5),
                    'end'   => substr($s->end_time,   0, 5),
                ]);

            return [
                'date'    => $d,
                'weekday' => $today->copy()->addDays($i)->locale('ja')->isoFormat('ddd'),
                'slots'   => $slots,
            ];
        });

        // ===== 写真ぼかし判定用の閲覧権限 =====
        $viewer = Auth::user();
        $viewerHasAccess = false;
        if ($viewer) {
            $viewerHasAccess = ($cast->user_id === $viewer->id)
                || (method_exists($viewer, 'isAdmin') && $viewer->isAdmin());
        }

        // 写真の取得
        $photosRaw = $cast->photos()->orderBy('sort_order')
            ->get(['id', 'path', 'sort_order', 'is_primary', 'should_blur']);

        // 個別許可（viewer_id / user_id どちらでも対応）
        $approvedMap = [];
        $pendingMap  = [];
        if ($viewer && $photosRaw->isNotEmpty()) {
            $photoIds = $photosRaw->pluck('id')->all();
            $viewerCol = Schema::hasColumn('cast_photo_view_permissions', 'viewer_id') ? 'viewer_id'
                : (Schema::hasColumn('cast_photo_view_permissions', 'user_id') ? 'user_id' : null);

            $permsQ = CastPhotoViewPermission::whereIn('cast_photo_id', $photoIds);
            if ($viewerCol) {
                $permsQ->where($viewerCol, $viewer->id);
            }
            $perms = $permsQ->orderByDesc('id')->get(['cast_photo_id', 'status']);

            foreach ($perms as $perm) {
                $pid = $perm->cast_photo_id;
                if ($perm->status === 'approved' && !isset($approvedMap[$pid])) {
                    $approvedMap[$pid] = true;
                } elseif ($perm->status === 'pending' && !isset($pendingMap[$pid])) {
                    $pendingMap[$pid]  = true;
                }
            }
        }

        // 写真の最終形に整形（boolean で揃える）
        $photos = $photosRaw->map(function ($p) use ($viewerHasAccess, $approvedMap, $pendingMap) {
            $isPrimary = (bool)$p->is_primary;
            $flagBlur  = (bool)$p->should_blur;
            $granted   = $viewerHasAccess || isset($approvedMap[$p->id]);
            $requested = isset($pendingMap[$p->id]);

            // ルール: primary は常に非ぼかし／それ以外は (should_blur && !granted)
            $should = $isPrimary ? false : ($flagBlur && !$granted);

            return [
                'id'          => $p->id,
                'url'         => Storage::disk('public')->url($p->path),
                'sort_order'  => (int)$p->sort_order,
                'is_primary'  => $isPrimary,
                'should_blur' => $should,
                'unblur'      => [
                    'granted'   => (bool)$granted,
                    'requested' => (bool)$requested,
                    'status'    => $granted ? 'approved' : ($requested ? 'pending' : null),
                ],
            ];
        });

        // 後方互換: photo_path（旧UI用）
        $primary = $cast->photos()->where('is_primary', true)->first()
            ?? $cast->photos()->orderBy('sort_order')->first();
        $photoPath = $primary?->path;

        // タグ配列化（文字列/配列両対応）
        $tags = $cast->tags;
        if (!is_array($tags)) {
            $tags = collect(preg_split('/[,\s、，]+/u', (string) $tags, -1, PREG_SPLIT_NO_EMPTY))
                ->values()->all();
        }

        
        // ===== ギフト（Show.vue用のprops） =====
        $gifts = Gift::active()
            ->get(['id', 'name', 'image_path', 'present_points', 'cast_points'])
            ->map(fn($g) => [
                'id'              => $g->id,
                'name'            => $g->name,
                'image_url'       => Storage::disk('public')->url($g->image_path),
                'present_points'  => (int) $g->present_points,
                'cast_points'     => (int) $g->cast_points,
            ]);

        $myBalance  = (int) (Auth::user()->points ?? 0);
        $lastGiftId = null;
        if (Auth::id()) {
            $lastGiftId = GiftSend::where('sender_user_id', Auth::id())
                ->where('cast_profile_id', $cast->id)
                ->latest()
                ->value('gift_id');
        }

        // ===== Inertia へ =====
        return Inertia::render('Cast/Show', [
            'cast' => [
                'id'         => $cast->id,
                'user_id'    => $cast->user_id,
                'nickname'   => $cast->nickname,
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

                'photo_path' => $photoPath,
                'photos'     => $photos,
                'viewer_has_unblur_access' => (bool) $viewerHasAccess,

                // ✅ 追加：ユーザーの最終ログイン時間を渡す
                'last_login_at' => $cast->user?->last_login_at,
            ],

            'schedule'     => $days,
            'gifts'        => $gifts,
            'my_balance'   => $myBalance,
            'last_gift_id' => $lastGiftId,
        ]);
    }

    /** 編集画面（スケジュール） */
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
                    'end'   => substr($s->end_time,   0, 5),
                ]);
            return ['date' => $d, 'slots' => $slots];
        });

        return Inertia::render('Cast/ScheduleEdit', [
            'castId' => $cast->id,
            'days'   => $days,
        ]);
    }

    /** 1週間をまとめて保存 */
    public function updateSchedule(Request $request, CastProfile $cast)
    {
        $this->authorizeEditing($cast);

        $data = $request->validate([
            'days'                 => ['required', 'array', 'size:7'],
            'days.*.date'          => ['required', 'date'],
            'days.*.slots'         => ['array'],
            'days.*.slots.*.start' => ['required_with:days.*.slots', 'regex:/^\d{2}:\d{2}$/'],
            'days.*.slots.*.end'   => ['required_with:days.*.slots', 'regex:/^\d{2}:\d{2}$/'],
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
