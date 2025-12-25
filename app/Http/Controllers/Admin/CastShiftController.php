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

        $shifts = CastShift::with(['castProfile:id,user_id,nickname', 'castProfile.user:id,name,email'])
            ->when($castId, fn($q) => $q->where('cast_profile_id', $castId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->paginate(50)
            ->withQueryString();

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

    public function store(Request $request)
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

        // (cast_profile_id, date, start_time) のユニークをアプリ側でも検知
        $request->validate([
            'start_time' => [
                Rule::unique('cast_shifts', 'start_time')->where(fn($q) =>
                    $q->where('cast_profile_id', $v['cast_profile_id'])
                      ->where('date', $v['date'])
                ),
            ],
        ]);

        CastShift::create([
            'cast_profile_id' => $v['cast_profile_id'],
            'date'            => $v['date'],
            'start_time'      => $v['start_time'],
            'end_time'        => $v['end_time'],
            'is_reserved'     => $request->boolean('is_reserved'),
        ]);

        return back()->with('success','シフトを作成しました');
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
