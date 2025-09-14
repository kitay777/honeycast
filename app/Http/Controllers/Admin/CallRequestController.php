<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRequest;
use App\Models\CallRequestCast;
use App\Models\CastProfile;
use App\Models\CastShift; // 既存の空き判定に利用
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Notifications\CallRequestInvited;

class CallRequestController extends Controller
{
    public function index(Request $request)
    {
        $status  = $request->string('status')->toString();   // pending/assigned/closed など
        $date    = $request->string('date')->toString();     // YYYY-MM or YYYY-MM-DD
        $selId   = $request->integer('selected_id') ?: null;

        $q = CallRequest::with(['user:id,name,email'])
            ->when($status !== '', fn($x)=> $x->where('status',$status))
            ->when($date   !== '', function($x) use($date) {
                if (strlen($date)===7) { // YYYY-MM
                    $x->where('date','>=',"$date-01")->where('date','<=',"$date-31");
                } else {
                    $x->where('date',$date);
                }
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // 選択中詳細（割当・候補）
        $selected = null;
        $candidates = [];
        if ($selId) {
            $selected = CallRequest::with([
                'user:id,name,email',
                'assignments.castProfile:id,user_id,nickname',
                'assignments.castProfile.user:id,name,email',
            ])->find($selId);

            if ($selected) {
                // 候補: CastShift に空きがあるキャスト（同日/時間を内包 & 未予約）
                $candidates = CastShift::with(['castProfile:id,user_id,nickname','castProfile.user:id,name,email'])
                    ->where('date', $selected->date)
                    ->where('start_time','<=',$selected->start_time)
                    ->where('end_time','>=',$selected->end_time)
                    ->where('is_reserved', false)
                    ->orderBy('cast_profile_id')
                    ->get()
                    ->map(function($s){
                        return [
                            'id'    => $s->cast_profile_id,
                            'label' => $s->castProfile->nickname ?? optional($s->castProfile->user)->name ?? ('#'.$s->cast_profile_id),
                            'email' => optional($s->castProfile->user)->email,
                        ];
                    })
                    ->unique('id')
                    ->values();
            }
        }

        // 全キャスト（手動検索/割当用）
        $allCasts = CastProfile::with('user:id,name,email')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id','user_id','nickname'])
            ->map(fn($c)=>[
                'id'=>$c->id,
                'label'=>$c->nickname ?: optional($c->user)->name ?: ('#'.$c->id),
                'email'=>optional($c->user)->email
            ]);

        return Inertia::render('Admin/Requests/Index', [
            'requests'   => $q,
            'selected'   => $selected,
            'candidates' => $candidates,
            'casts'      => $allCasts,
            'filters'    => ['status'=>$status,'date'=>$date,'selected_id'=>$selId],
        ]);
    }

    public function assign(Request $request, CallRequest $req)
    {
        $data = $request->validate([
            'cast_profile_id' => ['required','exists:cast_profiles,id'],
            'note'            => ['nullable','string','max:2000'],
        ]);

        \DB::transaction(function() use ($req, $data, $request) {
            $assign = \App\Models\CallRequestCast::firstOrCreate(
                [
                    'call_request_id' => $req->id,
                    'cast_profile_id' => $data['cast_profile_id'],
                ],
                [
                    'assigned_by' => optional($request->user())->id,
                    'status'      => 'invited',             // ← ここを invited に
                    'note'        => $data['note'] ?? null,
                    'invited_at'  => now(),
                ]
            );

            // 予約ブロックは「承諾後」に回すなら、ここはコメントアウトしてOK
            // …

            // 通知（メール/DB）
            $user = $assign->castProfile?->user;
            if ($user) {
                $user->notify(new CallRequestInvited($req, $assign));
            }

            // リクエスト自体の状態は pending のままでもOK（運用次第）
            if ($req->status === 'pending') {
                $req->status = 'assigned'; // or 'pending' のままでも可
                $req->save();
            }
        });

        return back()->with('success','招待を送りました');
    }

    public function unassign(Request $request, CallRequest $req, CallRequestCast $assignment)
    {
        abort_unless($assignment->call_request_id === $req->id, 404);
        $assignment->delete();
        return back()->with('success','割当を解除しました');
    }

    public function updateStatus(Request $request, CallRequest $req)
    {
        $request->validate(['status'=>['required','string','max:20']]);
        $req->status = $request->string('status');
        $req->save();
        return back()->with('success','ステータスを更新しました');
    }
}
