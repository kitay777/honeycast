<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GiftController extends Controller
{
    public function index(Request $r): Response
    {
        $q = trim((string) $r->get('q', ''));

        $items = Gift::query()
            ->when($q !== '', fn($qq) => $qq->where('name','like',"%{$q}%"))
            ->orderBy('priority')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return Inertia::render('Admin/Gifts/Index', [
            'items' => $items->through(function ($g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'image_url' => Storage::disk('public')->url($g->image_path),
                    'present_points' => (int)$g->present_points,
                    'cast_points'    => (int)$g->cast_points,
                    'is_active'      => (bool)$g->is_active,
                    'priority'       => (int)$g->priority,
                ];
            }),
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Gifts/Edit', ['item' => null]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'            => ['required','string','max:120'],
            'description'     => ['nullable','string','max:2000'],
            'image'           => ['required','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'present_points'  => ['required','integer','min:1','max:999999999'],
            'cast_points'     => ['required','integer','min:0','max:999999999'],
            'is_active'       => ['required','boolean'],
            'priority'        => ['nullable','integer','min:0','max:65535'],
        ]);

        if ((int)$data['cast_points'] > (int)$data['present_points']) {
            return back()->withErrors(['cast_points' => '受取ポイントはプレゼントポイント以下にしてください'])->withInput();
        }

        $path = $r->file('image')->store('gifts', 'public');

        Gift::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_path' => $path,
            'present_points' => (int)$data['present_points'],
            'cast_points'    => (int)$data['cast_points'],
            'is_active'      => (bool)$data['is_active'],
            'priority'       => $data['priority'] ?? 100,
        ]);

        return redirect()->route('admin.gifts.index')->with('success','プレゼントを登録しました');
    }

    public function edit(Gift $gift): Response
    {
        return Inertia::render('Admin/Gifts/Edit', [
            'item' => [
                'id' => $gift->id,
                'name' => $gift->name,
                'description' => $gift->description,
                'image_url' => Storage::disk('public')->url($gift->image_path),
                'present_points' => (int)$gift->present_points,
                'cast_points'    => (int)$gift->cast_points,
                'is_active'      => (bool)$gift->is_active,
                'priority'       => (int)$gift->priority,
            ],
        ]);
    }

    public function update(Request $r, Gift $gift)
    {
        $data = $r->validate([
            'name'            => ['required','string','max:120'],
            'description'     => ['nullable','string','max:2000'],
            'image'           => ['nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'present_points'  => ['required','integer','min:1','max:999999999'],
            'cast_points'     => ['required','integer','min:0','max:999999999'],
            'is_active'       => ['required','boolean'],
            'priority'        => ['nullable','integer','min:0','max:65535'],
        ]);

        if ((int)$data['cast_points'] > (int)$data['present_points']) {
            return back()->withErrors(['cast_points' => '受取ポイントはプレゼントポイント以下にしてください'])->withInput();
        }

        if ($r->hasFile('image')) {
            if ($gift->image_path && Storage::disk('public')->exists($gift->image_path)) {
                Storage::disk('public')->delete($gift->image_path);
            }
            $gift->image_path = $r->file('image')->store('gifts','public');
        }

        $gift->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'present_points' => (int)$data['present_points'],
            'cast_points'    => (int)$data['cast_points'],
            'is_active'      => (bool)$data['is_active'],
            'priority'       => $data['priority'] ?? $gift->priority,
        ])->save();

        return redirect()->route('admin.gifts.index')->with('success','プレゼントを更新しました');
    }

    public function destroy(Gift $gift)
    {
        if ($gift->image_path && Storage::disk('public')->exists($gift->image_path)) {
            Storage::disk('public')->delete($gift->image_path);
        }
        $gift->delete();
        return back()->with('success','プレゼントを削除しました');
    }
}
