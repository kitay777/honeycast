<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(): Response
    {
        $items = NewsItem::orderBy('priority')->orderByDesc('published_at')->orderByDesc('id')->paginate(20);
        return Inertia::render('Admin/News/Index', ['items' => $items]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/News/Edit', ['item' => null]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'        => ['required','string','max:200'],
            'body'         => ['nullable','string'],
            'url'          => ['nullable','url','max:2000'],
            'published_at' => ['nullable','date'],
            'is_active'    => ['required','boolean'],
            'priority'     => ['nullable','integer','min:0','max:65535'],
        ]);
        $data['priority'] = $data['priority'] ?? 100;
        NewsItem::create($data);
        return redirect()->route('admin.news.index')->with('success','新着情報を作成しました');
    }

    public function edit(NewsItem $news): Response
    {
        return Inertia::render('Admin/News/Edit', ['item' => $news]);
    }

    public function update(Request $r, NewsItem $news)
    {
        $data = $r->validate([
            'title'        => ['required','string','max:200'],
            'body'         => ['nullable','string'],
            'url'          => ['nullable','url','max:2000'],
            'published_at' => ['nullable','date'],
            'is_active'    => ['required','boolean'],
            'priority'     => ['nullable','integer','min:0','max:65535'],
        ]);
        $news->update($data);
        return redirect()->route('admin.news.index')->with('success','新着情報を更新しました');
    }

    public function destroy(NewsItem $news)
    {
        $news->delete();
        return back()->with('success','新着情報を削除しました');
    }
}
