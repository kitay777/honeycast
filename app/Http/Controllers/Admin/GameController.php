<?php
// app/Http/Controllers/Admin/GameController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $q = Game::orderBy('sort_order')->orderByDesc('id')->paginate(30)->withQueryString();

        return Inertia::render('Admin/Games/Index', [
            'games' => $q,
        ]);
    }

    /** 複数動画一括登録 */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['nullable','string','max:255'],
            'description'  => ['nullable','string','max:2000'],
            'is_published' => ['nullable','boolean'],
            'sort_order'   => ['nullable','integer'],
            'files'        => ['required','array','min:1'],
            'files.*'      => ['file','mimetypes:video/mp4,video/webm,video/quicktime,video/x-m4v','max:1024000'], // ~1GB
            'posters'      => ['sometimes','array'],
            'posters.*'    => ['image','mimes:jpg,jpeg,png,webp','max:10240'],
        ]);

        foreach ($request->file('files') as $i => $file) {
            $path = $file->store('games', 'public');
            $title = $data['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $posterPath = null;
            if ($request->hasFile("posters.$i")) {
                $posterPath = $request->file("posters.$i")->store('game_posters','public');
            }

            Game::create([
                'title'        => $title,
                'description'  => $data['description'] ?? null,
                'is_published' => (bool)($data['is_published'] ?? true),
                'sort_order'   => (int)($data['sort_order'] ?? 0),
                'file_path'    => $path,
                'mime_type'    => $file->getMimeType(),
                'size'         => $file->getSize(),
                'poster_path'  => $posterPath,
                'created_by'   => optional($request->user())->id,
            ]);
        }

        return back()->with('success', '動画を登録しました。');
    }

    public function update(Request $request, Game $game)
    {
        $data = $request->validate([
            'title'        => ['required','string','max:255'],
            'description'  => ['nullable','string','max:2000'],
            'is_published' => ['required','boolean'],
            'sort_order'   => ['required','integer'],
        ]);
        $game->update($data);
        return back()->with('success','更新しました。');
    }

    public function togglePublish(Game $game)
    {
        $game->is_published = ! $game->is_published;
        $game->save();
        return back();
    }

    public function destroy(Game $game)
    {
        Storage::disk('public')->delete($game->file_path);
        if ($game->poster_path) Storage::disk('public')->delete($game->poster_path);
        $game->delete();
        return back()->with('success','削除しました。');
    }
}
