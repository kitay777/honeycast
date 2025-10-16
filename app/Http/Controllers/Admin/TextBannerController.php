<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TextBanner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TextBannerController extends Controller
{
    // 一覧
    public function index(): Response
    {
        $items = TextBanner::orderBy('priority')->orderByDesc('id')->paginate(20);

        return Inertia::render('Admin/TextBanners/Index', [
            'items' => $items,
        ]);
    }

    // 作成フォーム
    public function create(): Response
    {
        return Inertia::render('Admin/TextBanners/Edit', [
            'item' => null,
        ]);
    }

    // 登録
    public function store(Request $req)
    {
        $data = $req->validate([
            'message'    => ['required', 'string', 'max:500'],
            'url'        => ['nullable', 'url', 'max:2000'],
            'speed'      => ['nullable', 'integer', 'min:10', 'max:500'],
            'is_active'  => ['required', 'boolean'],
            'starts_at'  => ['nullable', 'date'],
            'ends_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority'   => ['nullable', 'integer', 'min:0', 'max:65535'],
            'bg_color'   => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
        ]);

        $data['speed']    = $data['speed']    ?? 60;
        $data['priority'] = $data['priority'] ?? 100;
        $data['bg_color'] = $data['bg_color'] ?? '#111111';
        $data['text_color'] = $data['text_color'] ?? '#FFE08A';

        TextBanner::create($data);

        return redirect()->route('admin.text-banners.index')->with('success', 'テキストバナーを作成しました');
    }

    // 編集フォーム
    public function edit(TextBanner $text_banner): Response
    {
        return Inertia::render('Admin/TextBanners/Edit', [
            'item' => $text_banner,
        ]);
    }

    // 更新
    public function update(Request $req, TextBanner $text_banner)
    {
        $data = $req->validate([
            'message'    => ['required', 'string', 'max:500'],
            'url'        => ['nullable', 'url', 'max:2000'],
            'speed'      => ['nullable', 'integer', 'min:10', 'max:500'],
            'is_active'  => ['required', 'boolean'],
            'starts_at'  => ['nullable', 'date'],
            'ends_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority'   => ['nullable', 'integer', 'min:0', 'max:65535'],
            'bg_color'   => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
        ]);

        $text_banner->update($data);

        return redirect()->route('admin.text-banners.index')->with('success', 'テキストバナーを更新しました');
    }

    // 削除
    public function destroy(TextBanner $text_banner)
    {
        $text_banner->delete();
        return redirect()->back()->with('success', 'テキストバナーを削除しました');
    }
}
