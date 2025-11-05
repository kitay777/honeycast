<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CouponController extends Controller
{
    /** 一覧 */
    public function index()
    {
        $coupons = Coupon::latest()->paginate(20);

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons->through(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'discount_points' => $c->discount_points,
                'expires_at' => optional($c->expires_at)->format('Y-m-d'),
                'active' => (bool) $c->active,
                // ✅ public/storage 経由のURL
                'image_url' => $c->image_path ? Storage::url($c->image_path) : null,
            ]),
        ]);
    }

        /** 新規作成フォーム */
    public function create()
    {
        return Inertia::render('Admin/Coupons/Create');
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'code' => 'required|string|max:50|unique:coupons,code',
        'name' => 'required|string|max:100',
        'discount_points' => 'required|integer|min:1',
        'max_uses' => 'nullable|integer|min:1',
        'expires_at' => 'nullable|date',
        'active' => 'boolean',
        'image' => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('image')) {
        $data['image_path'] = $request->file('image')->store('coupons', 'public');
    }

    Coupon::create($data);

    return redirect()
        ->route('admin.coupons.index')
        ->with('success', 'クーポンを作成しました。');
}

    /** 編集画面 */
    public function edit(Coupon $coupon)
    {
        return Inertia::render('Admin/Coupons/Edit', [
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_points' => $coupon->discount_points,
                'max_uses' => $coupon->max_uses,
                'expires_at' => optional($coupon->expires_at)->format('Y-m-d'),
                'active' => (bool) $coupon->active,
                // ✅ モデルから直接URL生成
                'image_url' => $coupon->image_path ? Storage::url($coupon->image_path) : null,
            ],
        ]);
    }

    /** 更新 */
    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:100',
            'discount_points' => 'required|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        // ✅ 画像を上書き保存
        if ($request->hasFile('image')) {
            if ($coupon->image_path) {
                Storage::disk('public')->delete($coupon->image_path);
            }
            $data['image_path'] = $request->file('image')->store('coupons', 'public');
        }

        $coupon->update($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'クーポンを更新しました。');
    }
    /** 削除 */
    public function destroy(Coupon $coupon)
    {
        // 画像を削除（存在する場合）
        if ($coupon->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($coupon->image_path);
        }

        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'クーポンを削除しました。');
    }
}
