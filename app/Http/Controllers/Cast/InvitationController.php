<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CallRequestCast;
use App\Models\User;
use App\Notifications\CallRequestResponded;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvitationController extends Controller
{
    private function castProfileId(Request $request): int
    {
        $cp = $request->user()->castProfile;
        abort_unless($cp, 403);
        return $cp->id;
    }

    public function index(Request $request)
    {
        $cpId = $this->castProfileId($request);

        $invites = CallRequestCast::with(['callRequest','callRequest.user:id,name,email'])
            ->where('cast_profile_id', $cpId)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Cast/Invitations/Index', [
            'invites' => $invites,
        ]);
    }

    public function accept(Request $request, CallRequestCast $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->status = 'accepted';
        $assignment->responded_at = now();
        $assignment->save();

        // （必要ならここでシフトを予約済みに変更する）
        // ...

        $this->notifyAdmins($assignment, 'accept');

        return back()->with('success','参加で回答しました');
    }

    public function decline(Request $request, CallRequestCast $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->status = 'declined';
        $assignment->responded_at = now();
        $assignment->save();

        $this->notifyAdmins($assignment, 'decline');

        return back()->with('success','辞退で回答しました');
    }

    public function respondSigned(Request $request, CallRequestCast $assignment, string $decision)
    {
        // 署名URLからの1クリック応答（ログインしていなくてもOKにするなら here にゲスト→ログイン後処理を追加）
        abort_unless(in_array($decision, ['accept','decline'], true), 400);

        // 署名URLの場合は所属確認を厳に（assignmentのユーザーを特定できる時のみ通す）
        $user = $assignment->castProfile?->user;
        abort_unless($user, 403);

        $assignment->status = $decision === 'accept' ? 'accepted' : 'declined';
        $assignment->responded_at = now();
        $assignment->save();

        $this->notifyAdmins($assignment, $decision);

        return redirect()->route('login')->with('status', '回答ありがとうございました。ログインすると詳細が確認できます。');
    }

    private function authorizeAssignment(Request $request, CallRequestCast $assignment): void
    {
        $cpId = $this->castProfileId($request);
        abort_unless($assignment->cast_profile_id === $cpId, 403);
        abort_if(!in_array($assignment->status, ['invited','accepted','declined','confirmed']), 400);
    }

    private function notifyAdmins(CallRequestCast $assignment, string $decision): void
    {
        $req = $assignment->callRequest;
        $admins = \App\Models\User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new CallRequestResponded($req, $assignment, $decision));
        }
    }
}
