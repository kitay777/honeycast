<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $items = Event::orderBy('starts_at','desc')->paginate(20);
        return Inertia::render('Admin/Events/Index', ['items' => $items]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Edit', ['item' => null]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'      => ['required','string','max:200'],
            'body'       => ['nullable','string'],
            'place'      => ['nullable','string','max:200'],
            'starts_at'  => ['required','date'],
            'ends_at'    => ['nullable','date','after_or_equal:starts_at'],
            'is_all_day' => ['boolean'],
            'is_active'  => ['required','boolean'],
            'priority'   => ['nullable','integer','min:0','max:65535'],
            'image'      => ['nullable','image','max:8192'],
        ]);
        $path = $r->file('image')?->store('events', 'public');
        $data['image_path'] = $path;
        $data['priority'] ??= 100;

        Event::create($data);
        return redirect()->route('admin.events.index')->with('success','イベントを作成しました');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', [
            'item' => [
                'id'         => $event->id,
                'title'      => $event->title,
                'body'       => $event->body,
                'place'      => $event->place,
                'starts_at'  => optional($event->starts_at)->format('Y-m-d\TH:i'),
                'ends_at'    => optional($event->ends_at)->format('Y-m-d\TH:i'),
                'is_all_day' => $event->is_all_day,
                'is_active'  => $event->is_active,
                'priority'   => $event->priority,
                'image_url'  => $event->image_path ? Storage::disk('public')->url($event->image_path) : null,
            ],
        ]);
    }

    public function update(Request $r, Event $event)
    {
        $data = $r->validate([
            'title'      => ['required','string','max:200'],
            'body'       => ['nullable','string'],
            'place'      => ['nullable','string','max:200'],
            'starts_at'  => ['required','date'],
            'ends_at'    => ['nullable','date','after_or_equal:starts_at'],
            'is_all_day' => ['boolean'],
            'is_active'  => ['required','boolean'],
            'priority'   => ['nullable','integer','min:0','max:65535'],
            'image'      => ['nullable','image','max:8192'],
        ]);

        if ($r->hasFile('image')) {
            if ($event->image_path) Storage::disk('public')->delete($event->image_path);
            $event->image_path = $r->file('image')->store('events','public');
        }

        $event->fill($data)->save();

        return redirect()->route('admin.events.index')->with('success','イベントを更新しました');
    }

    public function destroy(Event $event)
    {
        if ($event->image_path) Storage::disk('public')->delete($event->image_path);
        $event->delete();
        return back()->with('success','イベントを削除しました');
    }
}
