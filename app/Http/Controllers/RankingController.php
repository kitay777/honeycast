<?php
namespace App\Http\Controllers;

use App\Models\CastProfile;
use Inertia\Inertia;

class RankingController extends Controller
{
public function index()
{
    $nominations = CastProfile::select('id','nickname','photo_path')
        ->orderByDesc('rank')->take(30)->get()->toArray();

    $access = CastProfile::select('id','nickname','photo_path')
        ->orderByDesc('updated_at')->take(30)->get()->toArray();

    $newbies = CastProfile::select('id','nickname','photo_path')
        ->orderByDesc('created_at')->take(30)->get()->toArray();

    return Inertia::render('Ranking', compact('nominations','access','newbies'));
}
}
