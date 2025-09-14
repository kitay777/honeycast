<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CastSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = CastProfile::query();

        if ($request->filled('freeword')) {
            $query->where('nickname', 'like', '%'.$request->freeword.'%')
                  ->orWhere('freeword', 'like', '%'.$request->freeword.'%');
        }

        if ($request->rank_min) $query->where('rank', '>=', $request->rank_min);
        if ($request->rank_max) $query->where('rank', '<=', $request->rank_max);

        if ($request->age_min) $query->where('age', '>=', $request->age_min);
        if ($request->age_max) $query->where('age', '<=', $request->age_max);

        if ($request->area) $query->where('area', $request->area);

        if ($request->tags) {
            foreach ($request->tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        $casts = $query->paginate(20);

        return Inertia::render('Cast/Search', [
            'casts' => $casts,
            'filters' => $request->all(),
        ]);
    }
}

