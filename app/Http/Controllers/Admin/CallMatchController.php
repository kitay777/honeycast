<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallMatch;
use Illuminate\Http\Request;

class CallMatchController extends Controller
{
    public function index(Request $request)
    {
        $matches = CallMatch::query()
            ->with(['castProfile.user', 'callRequest.user'])
            ->orderByDesc('started_at')
            ->paginate(30)
            ->withQueryString();

        return inertia('Admin/CallMatches/Index', [
            'matches' => $matches,
        ]);
    }

    public function show(CallMatch $match)
    {
        $match->load(['castProfile.user', 'callRequest.user']);

        // 延長履歴を別テーブルで管理していないなら、単純に duration_minutes を使う
        $extensions = [
            ['minutes' => $match->duration_minutes, 'updated_at' => $match->updated_at],
        ];

        return inertia('Admin/CallMatches/Show', [
            'match' => $match,
            'extensions' => $extensions,
        ]);
    }
}
