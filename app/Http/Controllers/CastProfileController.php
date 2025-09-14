<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CastProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $profile = $user->castProfile ?? CastProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            // まだプロフィール未作成なら空で返す（承認一覧は当然ゼロ）
            return Inertia::render('Cast/ProfileEdit', [
                'cast' => null,
                'profile' => null,
                'pendingPermissions' => [],
            ]);
        }

        // 承認待ち一覧
        $pendingPermissions = [];
        if (auth()->user()->can('manage', $profile)) {
            $pendingPermissions = $profile->viewPermissions()
                ->where('status', 'pending')
                ->with('viewer:id,name')  // 最小限の項目
                ->latest()->take(50)->get();
        }

        // tags を配列化（文字列なら分割）
        $tags = $profile->tags;
        if (!is_array($tags)) {
            $tags = collect(preg_split('/[,\s、，]+/u', (string) $tags, -1, PREG_SPLIT_NO_EMPTY))
                ->values()->all();
        }

        // Vue が使いやすい形で cast として返す（profile も残すなら同じ内容でOK）
        $cast = [
            'id'         => $profile->id,
            'nickname'   => $profile->nickname,
            'rank'       => $profile->rank,
            'age'        => $profile->age,
            'height_cm'  => $profile->height_cm,
            'cup'        => $profile->cup,
            'style'      => $profile->style,
            'alcohol'    => $profile->alcohol,
            'mbti'       => $profile->mbti,
            'area'       => $profile->area,
            'tags'       => $tags,
            'freeword'   => $profile->freeword,
            'photo_path' => $profile->photo_path,
        ];

        return Inertia::render('Cast/ProfileEdit', [
            'cast' => $cast,
            'profile' => $cast, // 互換のため残すなら
            'pendingPermissions' => $pendingPermissions,
        ]);
    }


    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'nickname'   => ['nullable', 'string', 'max:255'],
            'rank'       => ['nullable', 'integer', 'min:0', 'max:99'],
            'age'        => ['nullable', 'integer', 'min:18', 'max:99'],
            'height_cm'  => ['nullable', 'integer', 'min:120', 'max:220'],
            'cup'        => ['nullable', 'string', 'max:5'],
            'style'      => ['nullable', 'string', 'max:50'],
            'alcohol'    => ['nullable', 'string', 'max:50'],
            'mbti'       => ['nullable', 'string', 'max:4'],
            'area'       => ['nullable', 'string', 'max:255'],
            'tags'       => ['nullable', 'array'],
            'tags.*'     => ['string', 'max:30'],
            'freeword'   => ['nullable', 'string', 'max:2000'],
            'photo'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'], // 4MB
        ]);

        $profile = CastProfile::firstOrCreate(['user_id' => $user->id]);

        // 画像アップロード（あれば）
        if ($request->hasFile('photo')) {
            // 既存画像の削除
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
            $path = $request->file('photo')->store('cast_photos', 'public');
            $data['photo_path'] = $path;
        }

        $profile->fill($data);
        $profile->save();

        return back()->with('success', 'プロフィールを更新しました。');
    }
}
