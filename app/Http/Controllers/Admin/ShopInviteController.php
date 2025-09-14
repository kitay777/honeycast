<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ShopInviteController extends Controller
{
    public function store(Request $request, Shop $shop)
    {
        $invite = ShopInvite::create([
            'shop_id'   => $shop->id,
            'token'     => Str::random(24),
            'expires_at'=> $request->date('expires_at'),
            'max_uses'  => $request->integer('max_uses') ?: null,
            'created_by'=> $request->user()->id ?? null,
        ]);
        return back()->with('success','招待QRを発行しました');
    }

    // PNG QR を返す（キャッシュ1h）
    public function qr(ShopInvite $invite)
    {
        $url = route('shop.invite.capture', ['token' => $invite->token], true);
        $png = QrCode::format('png')->size(480)->margin(1)->generate($url);

        return response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function destroy(ShopInvite $invite)
    {
        $invite->delete();
        return back()->with('success','招待を削除しました');
    }
}
