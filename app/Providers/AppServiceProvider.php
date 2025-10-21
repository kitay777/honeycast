<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // ★ 本番 or 逆プロキシが https を示すときだけ https を強制
        if (app()->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        Inertia::share('chat', function () {
            if (!auth()->check()) return ['unread_total' => 0];

            $me = auth()->id();

            $threads = \App\Models\ChatThread::where(function ($q) use ($me) {
                $q->where('user_one_id', $me)->orWhere('user_two_id', $me);
            })->pluck('id');

            $unread = \App\Models\ChatMessage::whereIn('chat_thread_id', $threads)
                ->whereNull('read_at')
                ->where('sender_id', '!=', $me)
                ->count();

            return ['unread_total' => $unread];
        });
    }
}
