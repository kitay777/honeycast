<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NewbieController extends Controller
{
    /**
     * 新人一覧（直近3ヶ月に登録されたキャスト）
     * - 並び：created_at の新しい順
     * - ページネーションあり
     * - いいね状態 liked（ログイン時）
     * - 表示用に join_badge（登録日ラベル）/ is_new（30日以内強調）を付与
     */
    public function index(Request $request)
    {
        $tz    = config('app.timezone', 'Asia/Tokyo');
        $now   = Carbon::now($tz);
        $since = (clone $now)->subMonthsNoOverflow(3)->startOfDay();

        // 簡易検索 (?q=) — ニックネーム部分一致
        $kw = trim((string)$request->query('q', ''));

        $q = DB::table('cast_profiles as cp')
            ->where('cp.created_at', '>=', $since)
            ->select(['cp.id','cp.nickname','cp.photo_path','cp.is_blur_default','cp.created_at'])
            ->orderByDesc('cp.created_at');

        if ($kw !== '') {
            $q->where('cp.nickname', 'like', "%{$kw}%");
        }

        $casts = $q->paginate(24)->withQueryString();

        // ログインユーザーの liked
        $viewer = $request->user();
        $likedIds = $viewer
            ? DB::table('cast_likes')->where('user_id', $viewer->id)->pluck('cast_profile_id')->all()
            : [];

        // 表示用 shape（CastCard 互換 + ラベル）
        $items = $casts->through(function ($row) use ($tz, $likedIds, $now) {
            $created = Carbon::parse($row->created_at, $tz);
            return [
                'id'         => (int)$row->id,
                'nickname'   => $row->nickname,
                'photo_path' => $row->photo_path,
                'is_blur_default'          => (bool)$row->is_blur_default,
                'viewer_has_unblur_access' => false,
                'should_blur'              => false,
                'liked'      => in_array($row->id, $likedIds, true),

                // 追加情報
                'joined_at'  => $created->format('Y/m/d'),
                'join_badge' => $created->isoFormat('M/D（dd）'),
                'is_new'     => $created->gt((clone $now)->subDays(30)), // 30日以内は強調
            ];
        });

        return Inertia::render('Newbies/Index', [
            'items'   => $items,
            'filters' => ['q' => $kw],
            'since'   => $since->toDateString(),
        ]);
    }
}
