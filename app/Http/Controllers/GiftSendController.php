<?php

namespace App\Http\Controllers;

use App\Models\{Gift, CastProfile, GiftSend};
use App\Services\GiftSendService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

use Illuminate\Support\Facades\Storage;


class GiftSendController extends Controller
{
    public function store(Request $r, GiftSendService $svc)
    {
        $data = $r->validate([
            'cast_id' => ['required','exists:cast_profiles,id'],
            'gift_id' => ['required','exists:gifts,id'],
            'message' => ['nullable','string','max:200'],
        ]);

        $user = $r->user();
        $cast = CastProfile::findOrFail($data['cast_id']);
        $gift = Gift::findOrFail($data['gift_id']);

        try {
            $send = $svc->send($user, $cast, $gift, $data['message'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['gift' => $e->getMessage()]);
        }

        // Inertia の画面からならフラッシュ返し
        return back()->with('success', 'プレゼントを送付しました！');
    }

    // 送付一覧（ユーザー）
   public function mySends(Request $r)
    {
        $user = $r->user();

        $items = GiftSend::with(['cast:id,nickname,photo_path', 'gift:id,name,image_path'])
            ->where('sender_user_id', $user->id)
            ->latest()
            ->paginate(24)->withQueryString();

return Inertia::render('Gifts/MySends', [
    'items' => $items->through(fn($g) => [
        'id'     => $g->id,
        'gift'   => [
            'id'   => $g->gift_id,
            'name' => $g->gift?->name,
            'image_url' => $g->gift ? Storage::disk('public')->url($g->gift->image_path) : null, // ★追加
        ],
        'cast'   => [
            'id'         => $g->cast_profile_id,
            'nickname'   => $g->cast?->nickname,
            'photo_path' => $g->cast?->photo_path,
        ],
        'present_points' => (int)$g->present_points, // 自分が消費（表示する）
        // 'cast_points' は返さない（非表示）
        'message'        => $g->message,
        'created_at'     => $g->created_at?->format('Y/m/d H:i'),
    ]),
]);

    }


    public function castReceives(Request $request)
    {
        $user = $request->user();

        // user_id → cast_profile_id で確実に取得
        $cast = \App\Models\CastProfile::where('user_id', $user->id)->first();
        abort_unless($cast, 403, 'Cast profile not found.');

        $items = \App\Models\GiftSend::with(['sender:id,name','gift:id,name,image_path'])
            ->where('cast_profile_id', $cast->id)
            ->latest()
            ->paginate(24)
            ->through(fn($g) => [
                'id'     => $g->id,
                'gift'   => [
                    'id'   => $g->gift_id,
                    'name' => $g->gift?->name,
                    'image_url' => $g->gift ? Storage::disk('public')->url($g->gift->image_path) : null,
                ],
                'sender' => [
                    'id'   => $g->sender_user_id,
                    'name' => $g->sender?->name,
                ],
                'cast_points' => (int) $g->cast_points, // ★受け取ったポイント
                'message'     => $g->message,
                'created_at'  => optional($g->created_at)->format('Y/m/d H:i'),
            ]);

        return \Inertia\Inertia::render('Gifts/CastReceives', ['items' => $items]);
    }
}
