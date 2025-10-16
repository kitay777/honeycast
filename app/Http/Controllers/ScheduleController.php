<?php

namespace App\Http\Controllers;

use App\Models\CastShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        // ?offset=0 を基準に、今日から4日間（オフセットで前後移動）
        $offset = (int) $request->integer('offset', 0);
        $from   = Carbon::today()->addDays($offset);
        $to     = $from->copy()->addDays(3);

        $shifts = CastShift::with(['castProfile:id,nickname,photo_path'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->get();

        $days = collect(range(0,3))->map(function($i) use ($from, $shifts) {
            $d = $from->copy()->addDays($i)->toDateString();
            $items = $shifts->where('date', $d)->values()->map(function($s){
                // 24時跨ぎをケア
                $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $s->date.' '.$s->start_time);
                $end   = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $s->date.' '.$s->end_time);
                if ($s->end_time < $s->start_time) $end->addDay();

                return [
                    'id'          => $s->id,
                    'cast_id'     => $s->cast_profile_id,
                    'name'        => optional($s->castProfile)->nickname ?? '—',
                    'photo'       => optional($s->castProfile)->photo_path,
                    'start'       => $start->format('H:i'),
                    'end'         => $end->format('H:i'),
                    'is_reserved' => (bool)$s->is_reserved,
                ];
            });

            return [
                'date'  => $d,
                'label' => \Carbon\Carbon::createFromFormat('Y-m-d', $d)->isoFormat('M/D（dd）'),
                'items' => $items,
            ];
        });

        return Inertia::render('Schedule/Index', [
            'calendar4'  => $days,
            'offset'     => $offset,
            'rangeLabel' => $from->isoFormat('M/D（dd）').' 〜 '.$to->isoFormat('M/D（dd）'),
        ]);
    }
}