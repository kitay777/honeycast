<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HotelController extends Controller
{
    public function index(Request $r)
    {
        $kw   = trim((string)$r->get('q'));
        $area = trim((string)$r->get('area'));

        $q = Hotel::active();
        if ($kw !== '') {
            $q->where(function($w) use ($kw){
                $w->where('name','like',"%$kw%")
                  ->orWhere('address','like',"%$kw%");
            });
        }
        if ($area !== '') $q->where('area',$area);

        $items = $q->paginate(24)->withQueryString();

        return Inertia::render('Hotels/Index', [
            'items'   => $items->through(fn($h) => [
                'id'      => $h->id,
                'name'    => $h->name,
                'area'    => $h->area,
                'address' => $h->address,
                'phone'   => $h->phone,
                'website' => $h->website_url,
                'map'     => $h->map_url,
                'image'   => $h->cover_image_path ? Storage::disk('public')->url($h->cover_image_path) : null,
                'tags'    => $h->tags ?? [],
            ]),
            'filters' => ['q' => $kw, 'area' => $area],
            'areas'   => Hotel::query()->select('area')->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
        ]);
    }

    public function show(Hotel $hotel)
    {
        return Inertia::render('Hotels/Show', [
            'item' => [
                'id'      => $hotel->id,
                'name'    => $hotel->name,
                'area'    => $hotel->area,
                'address' => $hotel->address,
                'phone'   => $hotel->phone,
                'website' => $hotel->website_url,
                'map'     => $hotel->map_url,
                'image'   => $hotel->cover_image_path ? \Storage::disk('public')->url($hotel->cover_image_path) : null,
                'tags'    => $hotel->tags ?? [],
            ],
        ]);
    }
}
