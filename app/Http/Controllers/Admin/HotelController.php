<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller
{
    public function index(Request $r): Response
    {
        $viewer = $r->user();
        $q = Hotel::query()->orderBy('priority')->orderBy('name');
        if ($kw = trim((string)$r->get('q'))) {
            $q->where(function($w) use ($kw){
                $w->where('name','like',"%$kw%")
                  ->orWhere('area','like',"%$kw%")
                  ->orWhere('address','like',"%$kw%");
            });
        }
        $items = $q->paginate(20)->withQueryString();
    $likedIds = $viewer
        ? DB::table('cast_likes')
            ->where('user_id', $viewer->id)
            ->pluck('cast_profile_id')
            ->all()
        : [];
        return Inertia::render('Admin/Hotels/Index', [
            'items' => $items->through(fn($h) => [
                'id'     => $h->id,
                'name'   => $h->name,
                'area'   => $h->area,
                'active' => $h->is_active,
                'image'  => $h->cover_image_path ? Storage::disk('public')->url($h->cover_image_path) : null,
            ]),
            'filters' => ['q' => $kw],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Hotels/Edit', ['item' => null]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'          => ['required','string','max:200'],
            'area'          => ['nullable','string','max:100'],
            'address'       => ['nullable','string','max:255'],
            'phone'         => ['nullable','string','max:50'],
            'website_url'   => ['nullable','url','max:2000'],
            'map_url'       => ['nullable','url','max:2000'],
            'tags'          => ['nullable','array'],
            'is_active'     => ['required','boolean'],
            'priority'      => ['nullable','integer','min:0','max:65535'],
            'cover_image'   => ['nullable','image','max:8192'],
        ]);

        $path = $r->file('cover_image')?->store('hotels','public');
        $data['cover_image_path'] = $path;
        $data['priority'] ??= 100;

        Hotel::create($data);

        return redirect()->route('admin.hotels.index')->with('success','ホテルを登録しました');
    }

    public function edit(Hotel $hotel): Response
    {
        return Inertia::render('Admin/Hotels/Edit', [
            'item' => [
                'id'      => $hotel->id,
                'name'    => $hotel->name,
                'area'    => $hotel->area,
                'address' => $hotel->address,
                'phone'   => $hotel->phone,
                'website_url' => $hotel->website_url,
                'map_url'     => $hotel->map_url,
                'tags'        => $hotel->tags ?? [],
                'is_active'   => $hotel->is_active,
                'priority'    => $hotel->priority,
                'image_url'   => $hotel->cover_image_path ? Storage::disk('public')->url($hotel->cover_image_path) : null,
            ],
        ]);
    }

    public function update(Request $r, Hotel $hotel)
    {
        $data = $r->validate([
            'name'          => ['required','string','max:200'],
            'area'          => ['nullable','string','max:100'],
            'address'       => ['nullable','string','max:255'],
            'phone'         => ['nullable','string','max:50'],
            'website_url'   => ['nullable','url','max:2000'],
            'map_url'       => ['nullable','url','max:2000'],
            'tags'          => ['nullable','array'],
            'is_active'     => ['required','boolean'],
            'priority'      => ['nullable','integer','min:0','max:65535'],
            'cover_image'   => ['nullable','image','max:8192'],
        ]);

        if ($r->hasFile('cover_image')) {
            if ($hotel->cover_image_path) Storage::disk('public')->delete($hotel->cover_image_path);
            $hotel->cover_image_path = $r->file('cover_image')->store('hotels','public');
        }
        $hotel->fill($data)->save();

        return redirect()->route('admin.hotels.index')->with('success','ホテルを更新しました');
    }

    public function destroy(Hotel $hotel)
    {
        if ($hotel->cover_image_path) Storage::disk('public')->delete($hotel->cover_image_path);
        $hotel->delete();

        return back()->with('success','ホテルを削除しました');
    }
}
