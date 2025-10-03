<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->terms_accepted_version !== config('app.terms_version', '2025-09-28')) {
            // 規約ページと同意POSTへは通す
            if (!$request->routeIs(['terms.review','terms.accept','terms','logout'])) {
                return redirect()->route('terms.review');
            }
        }
        return $next($request);
    }
}
