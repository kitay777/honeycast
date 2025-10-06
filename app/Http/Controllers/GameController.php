<?php
// app/Http/Controllers/GameController.php
namespace App\Http\Controllers;

use App\Models\Game;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index()
    {
        $list = Game::where('is_published', true)
            ->orderBy('sort_order')->orderByDesc('id')
            ->get(['id','title','slug','poster_path','file_path'])
            ->map(fn($g)=>[
                'id'=>$g->id, 'title'=>$g->title, 'slug'=>$g->slug,
                'poster_url'=>$g->poster_url, 'video_url'=>$g->url,
            ]);

        return Inertia::render('Games/Index', ['games'=>$list]);
    }

    public function show(Game $game)
    {
        abort_unless($game->is_published, 404);

        return Inertia::render('Games/Show', [
            'game' => [
                'id'=>$game->id,
                'title'=>$game->title,
                'description'=>$game->description,
                'video_url'=>$game->url,
                'poster_url'=>$game->poster_url,
            ]
        ]);
    }
}
