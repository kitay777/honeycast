<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ShopController extends Controller
{


    public function index(Request $request)
    {
        $q = (string) $request->query('q','');

        $shops = Shop::when($q !== '', function($qb) use ($q) {
                    $qb->where('name','like',"%{$q}%")
                    ->orWhere('code','like',"%{$q}%")
                    ->orWhere('contact_email','like',"%{$q}%")
                    ->orWhere('contact_phone','like',"%{$q}%");
                })
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

        // どのショップを右ペインに表示するか
        $selectedId = $request->integer('selected_id') ?: optional($shops->first())->id;
        $selected   = $selectedId ? Shop::with('invites')->find($selectedId) : null;

        // ★ 追加：メンバー一覧（選択中ショップのユーザー）
        $members = null;
        if ($selectedId) {
            $members = User::where('shop_id', $selectedId)
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        return Inertia::render('Admin/Shops/Index', [
            'shops'      => $shops,
            'filters'    => ['q' => $q],
            'selected'   => $selected,
            'members'    => $members, // ★ 追加
            'captureUrl' => route('shop.invite.capture', ['token' => 'TOKEN_SAMPLE'], false),
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'code'  => ['nullable','string','max:50','unique:shops,code'],
            'contact_email' => ['nullable','email','max:255'],
            'contact_phone' => ['nullable','string','max:50'],
            'is_active'     => ['boolean'],
            'note'          => ['nullable','string','max:2000'],
        ]);
        if (empty($data['code'])) $data['code'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        Shop::create($data);
        return back()->with('success','ショップを作成しました');
    }

    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name'  => ['sometimes','required','string','max:255'],
            'code'  => ['sometimes','required','string','max:50', Rule::unique('shops','code')->ignore($shop->id)],
            'contact_email' => ['nullable','email','max:255'],
            'contact_phone' => ['nullable','string','max:50'],
            'is_active'     => ['boolean'],
            'note'          => ['nullable','string','max:2000'],
        ]);
        $shop->fill($data)->save();
        return back()->with('success','更新しました');
    }

    public function destroy(Shop $shop)
    {
        $shop->delete();
        return back()->with('success','削除しました');
    }
}
