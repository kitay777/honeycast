<?php
namespace App\Http\Controllers;

use App\Models\CastProfile;
use Illuminate\Http\Request;

class CastLikeController extends Controller
{
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
