<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shop = Shop::with(['invites:id,shop_id,token,used_count,created_at'])
                ->findOrFail($user->shop_id);

        $members = User::where('shop_id', $shop->id)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Owner/Shop/Portal', [
            'shop'       => ['id'=>$shop->id,'name'=>$shop->name,'code'=>$shop->code],
            'invites'    => $shop->invites->map(fn($i)=>[
                               'id'=>$i->id,'token'=>$i->token,
                               'used_count'=>$i->used_count,'created_at'=>$i->created_at,
                             ]),
            'members'    => $members,
            'captureUrl' => route('shop.invite.capture', ['token'=>'TOKEN_SAMPLE'], false),
        ]);
    }
}
