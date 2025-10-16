<?php
namespace App\Http\Controllers;

use App\Models\CastProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CastLikeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 自分のいいねをJOINしてキャストを取得（最終いいね時刻で新しい順）
        $casts = CastProfile::query()
            ->join('cast_likes', 'cast_likes.cast_profile_id', '=', 'cast_profiles.id')
            ->where('cast_likes.user_id', $user->id)
            ->orderByDesc('cast_likes.created_at')
            ->select([
                'cast_profiles.id',
                'cast_profiles.nickname',
                'cast_profiles.photo_path',
                'cast_profiles.is_blur_default',
                'cast_likes.created_at as liked_at',
            ])
            ->paginate(24)
            ->withQueryString();

        // フロントで CastCard を使いやすい形に
        $items = $casts->through(function ($c) {
            return [
                'id'         => $c->id,
                'nickname'   => $c->nickname,
                'photo_path' => $c->photo_path,
                'is_blur_default'          => true,
                'viewer_has_unblur_access' => false,
                'should_blur'              => false,
                'liked'      => true,                 // ここは確実に true
                'liked_at'   => optional($c->liked_at)?->format('Y/m/d H:i'),
            ];
        });

        return Inertia::render('Likes/Index', [
            'items' => $items,
        ]);
    }

    public function store(Request $request, CastProfile $cast)
    {
        $request->user()->castLikes()->firstOrCreate([
            'cast_profile_id' => $cast->id,
        ]);
        // Inertia の一覧に戻る。必要ならフラッシュメッセージも。
        return back();
    }

    public function destroy(Request $request, CastProfile $cast)
    {
        $request->user()->castLikes()->where('cast_profile_id', $cast->id)->delete();
        return back();
    }
}
