<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopInviteUsage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InviteUsageController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->integer('shop_id') ?: null;

        $logs = ShopInviteUsage::with([
                'invite.shop:id,name',
                'user:id,name,email',
            ])
            ->when($shopId, fn($q) => $q->whereHas('invite', fn($qq) => $qq->where('shop_id', $shopId)))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $shops = Shop::orderBy('name')->get(['id','name']);

        return Inertia::render('Admin/Invites/Logs', [
            'logs'   => $logs,
            'shops'  => $shops,
            'filter' => ['shop_id' => $shopId],
        ]);
    }
}
