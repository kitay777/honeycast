<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator; 
use Inertia\Inertia;
use App\Models\ChatThread;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(UrlGenerator $url): void
    {
        Vite::prefetch(concurrency: 3);
        $url->forceScheme('https');
        Inertia::share('chat', function () {
            if (!auth()->check()) return ['unread_total'=>0];
            $me = auth()->id();

            $threads = ChatThread::query()
                ->where(fn($q)=>$q->where('user_one_id',$me)->orWhere('user_two_id',$me))
                ->pluck('id');

            $unread = \App\Models\ChatMessage::whereIn('chat_thread_id',$threads)
                ->whereNull('read_at')
                ->where('sender_id','!=',$me)
                ->count();

            return ['unread_total'=>$unread];
        });
    }
}
