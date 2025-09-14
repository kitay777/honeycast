<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ShopInvite;
use Illuminate\Http\Request;

class InviteCaptureController extends Controller
{
// app/Http/Controllers/Public/InviteCaptureController.php
    public function __invoke(Request $request, string $token)
    {
        $invite = ShopInvite::where('token',$token)->first();
        if (!$invite || !$invite->isValid()) return redirect()->route('register');

        if ($request->user()) {
            $user = $request->user();
            if (is_null($user->shop_id)) {        // 既に所属が無いときだけ付与（運用方針に合わせて）
                $user->shop_id = $invite->shop_id;
                $user->save();
            }
            $invite->increment('used_count');
            ShopInviteUsage::firstOrCreate(
                ['shop_invite_id'=>$invite->id,'user_id'=>$user->id],
                ['ip'=>$request->ip(),'user_agent'=>(string)$request->userAgent()]
            );
            return redirect()->route('dashboard')->with('success','ショップと連携しました');
        }

        // 未ログイン/未登録なら登録フローへ
        $request->session()->put('shop_token', $token);
        return redirect()->route('register');
    }

}
