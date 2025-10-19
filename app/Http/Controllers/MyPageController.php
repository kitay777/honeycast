<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class MyPageController extends Controller
{
    public function points(Request $req)
    {
        $u = $req->user()->load(['pointsEntries' => fn($q) => $q->latest()->limit(20)]);
        return Inertia::render('My/Points', [
            'balance' => (int)$u->points,
            'history' => $u->pointsEntries->map(fn($e) => [
                'id' => $e->id,
                'delta' => (int)$e->delta,
                'after' => (int)$e->balance_after,
                'reason'=> $e->reason,
                'acted_by' => $e->acted_by,
                'created_at'=> $e->created_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }
}

