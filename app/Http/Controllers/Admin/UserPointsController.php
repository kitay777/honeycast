<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PointsEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserPointsController extends Controller
{
    public function index(Request $r)
    {
        $kw = trim((string)$r->query('q', ''));
        $q = User::query()->select('id', 'name', 'email', 'points')->orderByDesc('points');
        if ($kw !== '') {
            $q->where(fn($w) => $w->where('name', 'like', "%$kw%")->orWhere('email', 'like', "%$kw%"));
        }
        $users = $q->paginate(20)->withQueryString();
        return Inertia::render('Admin/Points/Index', [
            'users' => $users,
            'filters' => ['q' => $kw],
        ]);
    }

    public function adjust(Request $r)
    {
        $data = $r->validate([
            'user_id' => ['required', 'exists:users,id'],
            'delta'   => ['required', 'integer', 'between:-100000000,100000000'],
            'reason'  => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $r) {
            $u = \App\Models\User::whereKey($data['user_id'])->lockForUpdate()->first();
            $new = $u->points + (int)$data['delta'];
            if ($new < 0) throw new \RuntimeException('残高がマイナスになります');
            $u->points = $new;
            $u->save();

            PointsEntry::create([
                'user_id'       => $u->id,
                'delta'         => (int)$data['delta'],
                'balance_after' => $new,
                'reason'        => $data['reason'] ?? null,
                'acted_by'      => $r->user()->id,
            ]);
        });

        return back()->with('success', 'ポイントを調整しました');
    }
    public function show(\App\Models\User $user)
    {
        $entries = $user->pointsEntries()->latest()->limit(20)->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'delta' => (int) $e->delta,
                'after' => (int) $e->balance_after,
                'reason' => $e->reason,
                'acted_by' => $e->acted_by,
                'created_at' => $e->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'user_id' => $user->id,
            'balance' => (int) $user->points,
            'history' => $entries,
        ]);
    }

public function adjustUser(Request $r, User $user)
{
    $data = $r->validate([
        'delta'  => ['required','integer','between:-100000000,100000000'],
        'reason' => ['nullable','string','max:255'],
    ]);

    $actorId = $r->user()->id;

    DB::transaction(function () use ($user, $data, $actorId) {
        $u = User::whereKey($user->id)->lockForUpdate()->first();
        $new = $u->points + (int)$data['delta'];
        if ($new < 0) {
            throw new \RuntimeException('残高がマイナスになります');
        }
        $u->points = $new; $u->save();

        PointsEntry::create([
            'user_id'       => $u->id,
            'delta'         => (int)$data['delta'],
            'balance_after' => $new,
            'reason'        => $data['reason'] ?? null,
            'acted_by'      => $actorId,
        ]);
    });

    // ★ Inertiaに正しいレスポンス（303 See Other）を返す
    //    -> 同一画面に戻って props を再取得
    return back(303)->with('success', 'ポイントを調整しました');
}
}
