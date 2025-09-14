<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\CastProfile;
use App\Models\CastShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReserveController extends Controller
{
    /** 予約フォーム */
    public function create(Request $request)
    {
        // とりあえず全キャスト表示（必要なら絞り込み：today のみ等）
        $casts = CastProfile::select('id','nickname','photo_path')->orderBy('nickname')->get();
        return Inertia::render('Reserve', [
            'casts' => $casts,
        ]);
    }

    /** 予約保存 */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cast_profile_id' => ['required','exists:cast_profiles,id'],
            'date'            => ['required','date'],
            'start_time'      => ['required','date_format:H:i'],
            'end_time'        => ['required','date_format:H:i','after:start_time'],
            'payment_method'  => ['nullable','string','max:20'],
            'note'            => ['nullable','string','max:2000'],
        ]);

        // 1) シフト内に収まっているか（該当日の枠のどれかに完全に内包されるか）
        $containsShift = CastShift::where('cast_profile_id', $data['cast_profile_id'])
            ->whereDate('date', $data['date'])
            ->get(['start_time','end_time'])
            ->contains(function($s) use ($data) {
                return ($data['start_time'] >= $s->start_time) && ($data['end_time'] <= $s->end_time);
            });

        if (!$containsShift) {
            return back()->withErrors(['start_time' => '選択時間がキャストのシフト外です。'])
                         ->withInput();
        }

        // 2) 既存予約との重複がないか
        $overlap = Reservation::where('cast_profile_id', $data['cast_profile_id'])
            ->whereDate('date', $data['date'])
            ->where(function($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                  ->orWhereBetween('end_time',   [$data['start_time'], $data['end_time']])
                  ->orWhere(function($q2) use ($data) { // 既存の方が完全に被せる
                      $q2->where('start_time','<=',$data['start_time'])
                         ->where('end_time','>=',$data['end_time']);
                  });
            })->exists();

        if ($overlap) {
            return back()->withErrors(['start_time' => '既に同時間帯の予約があります。別の時間を選択してください。'])
                         ->withInput();
        }

        // 保存
        $reservation = Reservation::create([
            'user_id'         => Auth::id(),
            'cast_profile_id' => $data['cast_profile_id'],
            'date'            => $data['date'],
            'start_time'      => $data['start_time'],
            'end_time'        => $data['end_time'],
            'payment_method'  => $data['payment_method'] ?? null,
            'note'            => $data['note'] ?? null,
            'status'          => 'pending',
        ]);

        return redirect()->route('reserve.show', $reservation->id)
            ->with('success', '予約を受け付けました。');
    }

    /** 確認ページ */
    public function show(Reservation $reservation)
    {
        $reservation->load(['castProfile:id,nickname,photo_path','user:id,name']);

        return Inertia::render('ReserveShow', [
            'reservation' => [
                'id' => $reservation->id,
                'date' => $reservation->date->toDateString(),
                'start_time' => substr($reservation->start_time,0,5),
                'end_time'   => substr($reservation->end_time,0,5),
                'status'     => $reservation->status,
                'payment_method' => $reservation->payment_method,
                'note'       => $reservation->note,
                'cast'       => [
                    'id' => $reservation->castProfile->id,
                    'nickname' => $reservation->castProfile->nickname,
                    'photo_path' => $reservation->castProfile->photo_path,
                ],
                'user' => [
                    'id' => $reservation->user->id,
                    'name' => $reservation->user->name,
                ],
            ],
        ]);
    }
}
