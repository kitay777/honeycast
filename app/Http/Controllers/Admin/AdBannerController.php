<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdBannerController extends Controller
{
    // 一覧
    public function index(): Response
    {
        $items = AdBanner::orderBy('priority')->orderByDesc('id')->paginate(20);

        return Inertia::render('Admin/AdBanners/Index', [
            'items' => $items->through(function ($b) {
                return [
                    'id'        => $b->id,
                    'url'       => $b->url,
                    'height'    => $b->height,
                    'is_active' => $b->is_active,
                    'starts_at' => $b->starts_at,
                    'ends_at'   => $b->ends_at,
                    'priority'  => $b->priority,
                    'image_url' => Storage::disk('public')->url($b->image_path),
                ];
            }),
        ]);
    }

    // 作成フォーム
    public function create(): Response
    {
        return Inertia::render('Admin/AdBanners/Edit', [
            'item' => null,
        ]);
    }

    // 登録
    public function store(Request $req)
    {
        $data = $req->validate([
            'image'     => ['required', 'image', 'max:8192'], // 8MB
            'url'       => ['nullable', 'url', 'max:2000'],
            'height'    => ['nullable', 'integer', 'min:60', 'max:600'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority'  => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        // 画像保存（public ディスク）
        $path = $req->file('image')->store('ad-banners', 'public');

        AdBanner::create([
            'image_path' => $path,
            'url'        => $data['url'] ?? null,
            'height'     => $data['height'] ?? 120,
            'is_active'  => $data['is_active'],
            'starts_at'  => $data['starts_at'] ?? null,
            'ends_at'    => $data['ends_at'] ?? null,
            'priority'   => $data['priority'] ?? 100,
        ]);

        return redirect()->route('admin.ad-banners.index')->with('success', '広告バナーを作成しました');
    }

    // 編集フォーム
    public function edit(AdBanner $ad_banner): Response
    {
        return Inertia::render('Admin/AdBanners/Edit', [
            'item' => [
                'id'        => $ad_banner->id,
                'url'       => $ad_banner->url,
                'height'    => $ad_banner->height,
                'is_active' => $ad_banner->is_active,
                'starts_at' => $ad_banner->starts_at,
                'ends_at'   => $ad_banner->ends_at,
                'priority'  => $ad_banner->priority,
                'image_url' => Storage::disk('public')->url($ad_banner->image_path),
            ],
        ]);
    }

    // 更新
    public function update(Request $req, AdBanner $ad_banner)
    {
        $data = $req->validate([
            'image'     => ['nullable', 'image', 'max:8192'],
            'url'       => ['nullable', 'url', 'max:2000'],
            'height'    => ['nullable', 'integer', 'min:60', 'max:600'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority'  => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        // 画像更新がある場合は差し替え
        if ($req->hasFile('image')) {
            // 旧ファイル削除
            if ($ad_banner->image_path && Storage::disk('public')->exists($ad_banner->image_path)) {
                Storage::disk('public')->delete($ad_banner->image_path);
            }
            $newPath = $req->file('image')->store('ad-banners', 'public');
            $ad_banner->image_path = $newPath;
        }

        $ad_banner->url       = $data['url'] ?? null;
        $ad_banner->height    = $data['height'] ?? $ad_banner->height;
        $ad_banner->is_active = $data['is_active'];
        $ad_banner->starts_at = $data['starts_at'] ?? null;
        $ad_banner->ends_at   = $data['ends_at'] ?? null;
        $ad_banner->priority  = $data['priority'] ?? $ad_banner->priority;
        $ad_banner->save();

        return redirect()->route('admin.ad-banners.index')->with('success', '広告バナーを更新しました');
    }

    // 削除
    public function destroy(AdBanner $ad_banner)
    {
        // 画像も削除
        if ($ad_banner->image_path && Storage::disk('public')->exists($ad_banner->image_path)) {
            Storage::disk('public')->delete($ad_banner->image_path);
        }
        $ad_banner->delete();

        return redirect()->back()->with('success', '広告バナーを削除しました');
    }
}
