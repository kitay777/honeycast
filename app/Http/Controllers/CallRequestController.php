<?php
namespace App\Http\Controllers;

use App\Models\CallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CallRequestController extends Controller
{
    public function create(Request $request)
    {
        // 事前入力（?place=...&nom=... など）を反映したい場合はここで渡してもOK
        return Inertia::render('Call/Create', [
            'defaults' => [
                'place'       => $request->query('place'),
                'nomination'  => $request->query('nom'),
            ],
        ]);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'place'        => ['required','string','max:255'],
            'cast_count'   => ['required','integer','min:1','max:50'],
            'guest_count'  => ['required','integer','min:1','max:200'],
            'nomination'   => ['nullable','string','max:255'],
            'date'         => ['required','date'],
            'start_time'   => ['required','date_format:H:i'],
            'end_time'     => ['required','date_format:H:i','after:start_time'],
            'set_plan'     => ['nullable','string','max:50'],
            'game_option'  => ['nullable','string','max:255'],
            'note'         => ['nullable','string','max:2000'],
        ]);
        $call = CallRequest::create([
            'user_id'      => Auth::id(),
            ...$data,
            'status'       => 'pending',
        ]);

        return redirect()->route('call.show', $call->id)->with('success', '呼び出しリクエストを受け付けました。');
    }

    public function show(CallRequest $callRequest)
    {
        $this->authorizeView($callRequest);

        return Inertia::render('Call/Show', [
            'call' => [
                'id'          => $callRequest->id,
                'place'       => $callRequest->place,
                'cast_count'  => $callRequest->cast_count,
                'guest_count' => $callRequest->guest_count,
                'nomination'  => $callRequest->nomination,
                'date'        => $callRequest->date->toDateString(),
                'start_time'  => substr($callRequest->start_time,0,5),
                'end_time'    => substr($callRequest->end_time,0,5),
                'set_plan'    => $callRequest->set_plan,
                'game_option' => $callRequest->game_option,
                'note'        => $callRequest->note,
                'status'      => $callRequest->status,
            ],
        ]);
    }

    private function authorizeView(CallRequest $callRequest): void
    {
        if ($callRequest->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
