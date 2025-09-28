<?php
namespace App\Http\Controllers;

use App\Models\CastPhoto;
use App\Models\CastPhotoViewPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CastPhotoPermissionController extends Controller
{
    // 閲覧者 → 申請
    public function store(Request $request, CastPhoto $castPhoto)
    {
        $data = $request->validate(['message'=>['nullable','string','max:1000']]);

        CastPhotoViewPermission::updateOrCreate(
            ['cast_photo_id'=>$castPhoto->id, 'viewer_user_id'=>Auth::id()],
            ['status'=>'pending','message'=>$data['message'] ?? null, 'granted_by_user_id'=>null, 'expires_at'=>null]
        );

        return back()->with('success','写真のぼかし解除を申請しました。');
    }

    // キャスト本人 → 承認
    public function approve(Request $request, CastPhoto $castPhoto, CastPhotoViewPermission $permission)
    {
        // キャスト本人/管理者か？（CastProfilePolicy@manage を利用）
        $this->authorize('manage', $castPhoto->castProfile);

        abort_unless($permission->cast_photo_id === $castPhoto->id, 404);

        $request->validate(['expires_at'=>['nullable','date']]);

        $permission->update([
            'status'=>'approved',
            'granted_by_user_id'=>Auth::id(),
            'expires_at'=>$request->input('expires_at'),
        ]);

        return back()->with('success','写真の申請を承認しました。');
    }

    // 否認
    public function deny(Request $request, CastPhoto $castPhoto, CastPhotoViewPermission $permission)
    {
        $this->authorize('manage', $castPhoto->castProfile);
        abort_unless($permission->cast_photo_id === $castPhoto->id, 404);

        $permission->update([
            'status'=>'denied',
            'granted_by_user_id'=>Auth::id(),
            'expires_at'=>null,
        ]);

        return back()->with('success','写真の申請を否認しました。');
    }
}
