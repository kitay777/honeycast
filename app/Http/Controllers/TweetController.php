<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tweet;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TweetController extends Controller
{
    public function index()
    {
        $tweets = Tweet::with(['user:id,name','castProfile:id,nickname,photo_path'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Tweet/Index', [
            'tweets' => $tweets,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tweet/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable','string','max:255'],
            'body'  => ['required','string','max:2000'],
            'image' => ['nullable','image','max:4096'],
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tweets','public');
        }

        Tweet::create([
            'user_id'        => Auth::check() ? Auth::id() : null,
            'cast_profile_id'=> Auth::user()->castProfile->id ?? null,
            'title'          => $data['title'] ?? null,
            'body'           => $data['body'],
            'image_path'     => $path,
        ]);

        return redirect()->route('tweets.index')->with('success','ツイートを投稿しました');
    }
}