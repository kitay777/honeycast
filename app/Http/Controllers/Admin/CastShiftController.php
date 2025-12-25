<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastProfile;
use App\Models\CastShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CastShiftController extends Controller
{
    public function index(Request $request)
    {
        $month  = $request->string('month')->toString(); // 'YYYY-MM' or ''
        $castId = $request->integer('cast_id') ?: null;

        $start = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $casts = CastProfile::with('user:id,name,email')
            ->orderByDesc('id')
            ->get(['id','user_id','nickname','area']);

$shifts = CastShift::with(['castProfile.user'])
    ->when($castId, fn($q) => $q->where('cast_profile_id', $castId))
    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
    ->orderBy('date')
    ->orderBy('start_time')
    ->paginate(50)
    ->through(fn ($s) => [
        'id' => $s->id,
        'date' => \Carbon\Carbon::parse($s->date)->format('Y-m-d'),
        'start_time' => $s->start_time,
        'end_time' => $s->end_time,
        'is_reserved' => $s->is_reserved,
        'cast_profile_id' => $s->cast_profile_id,
        'cast_profile' => [
            'nickname' => $s->castProfile?->nickname,
            'user' => [
                'name' => $s->castProfile?->user?->name,
            ],
        ],
    ]);


        return Inertia::render('Admin/Schedules/Index', [
            'month'   => $start->format('Y-m'),
            'casts'   => $casts->map(fn($c) => [
                            'id'    => $c->id,
                            'label' => $c->nickname ?: optional($c->user)->name ?: ('#'.$c->id),
                            'email' => optional($c->user)->email,
                        ]),
            'cast_id' => $castId,
            'schedules' => $shifts,
        ]);
    }

public function store(Request $request, int $castId)
{
    $data = $request->validate([
        'days' => ['required','array'],
        'days.*.date' => ['required','date'],
        'days.*.slots' => ['array'],
        'days.*.slots.*.start' => ['required','date_format:H:i'],
        'days.*.slots.*.end'   => [
            'required',
            'regex:/^(?:[0-9]|[01][0-9]|2[0-9]|3[0-4]):[0-5][0-9]$/'
        ],
    ]);

    foreach ($data['days'] as $day) {

        // ★ その日の分は一旦全削除（完全同期）
        CastShift::where('cast_profile_id', $castId)
            ->where('date', $day['date'])
            ->delete();

        // 再登録
        foreach ($day['slots'] ?? [] as $slot) {
            CastShift::create([
                'cast_profile_id' => $castId,
                'date'            => $day['date'],
                'start_time'      => $slot['start'],
                'end_time'        => $slot['end'],
                'is_reserved'     => false,
            ]);
        }
    }

    return back()->with('success', 'スケジュールを保存しました');
}



    public function update(Request $request, CastShift $shift)
    {
        $v = $request->validate([
            'cast_profile_id' => ['required','exists:cast_profiles,id'],
            'date'            => ['required','date'],
            'start_time'      => ['required','date_format:H:i'],
                        'end_time' => [
                    'required',
                    'regex:/^(?:[0-9]|[01][0-9]|2[0-9]|3[0-4]):[0-5][0-9]$/',
                ],
            'is_reserved'     => ['boolean'],
        ]);

        // ユニーク制約（自分は除外）
        $request->validate([
            'start_time' => [
                Rule::unique('cast_shifts', 'start_time')
                    ->ignore($shift->id)
                    ->where(fn($q) => $q->where('cast_profile_id', $v['cast_profile_id'])
                                      ->where('date', $v['date'])),
            ],
        ]);

        $shift->fill([
            'cast_profile_id' => $v['cast_profile_id'],
            'date'            => $v['date'],
            'start_time'      => $v['start_time'],
            'end_time'        => $v['end_time'],
            'is_reserved'     => $request->boolean('is_reserved'),
        ])->save();

        return back()->with('success','更新しました');
    }

    public function destroy(CastShift $shift)
    {
        $shift->delete();
        return back()->with('success','削除しました');
    }
}
