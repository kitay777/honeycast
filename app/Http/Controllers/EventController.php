<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index(Request $request)
    {
        // 直近/今後を上にしたいなら starts_at >= now の並びにしてもOK
        $items = Event::active()
            ->orderBy('starts_at', 'asc')
            ->get(['id','title','body','place','starts_at','ends_at','is_all_day','image_path']);

        return Inertia::render('Events/Index', [
            'items' => $items->map(fn($e) => [
                'id'        => $e->id,
                'title'     => $e->title,
                'body'      => $e->body,
                'place'     => $e->place,
                'starts_at' => optional($e->starts_at)?->format('Y/m/d H:i'),
                'ends_at'   => optional($e->ends_at)?->format('Y/m/d H:i'),
                'is_all_day'=> $e->is_all_day,
                'image'     => $e->image_path ? \Storage::disk('public')->url($e->image_path) : null,
            ]),
        ]);
    }
}
