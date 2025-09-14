<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ShopInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InviteController extends Controller
{
    public function store(Request $request)
    {
        $invite = ShopInvite::create([
            'shop_id'   => $request->user()->shop_id,
            'token'     => Str::random(24),
            'created_by'=> $request->user()->id ?? null,
        ]);
        return back()->with('success','QRを発行しました');
    }

    public function qr(Request $request, ShopInvite $invite)
    {
        abort_unless($invite->shop_id === $request->user()->shop_id, 403);
        $url = route('shop.invite.capture', ['token'=>$invite->token], true);
        $png = QrCode::format('png')->size(480)->margin(1)->generate($url);
        return response($png, 200, ['Content-Type'=>'image/png','Cache-Control'=>'public, max-age=3600']);
    }
}
