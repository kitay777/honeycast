<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TermsController extends Controller
{
    // 公開ページ（誰でも閲覧可）
    public function show(Request $request)
    {
        return Inertia::render('Public/Terms', [
            'version'  => config('app.terms_version', '2025-09-28'),
            'mode'     => 'public',
            'accepted' => false,
        ]);
    }
    public function unei(Request $request)
    {
        return Inertia::render('Public/Unei', [
            'version'  => config('app.terms_version', '2025-09-28'),
            'mode'     => 'public',
            'accepted' => false,
        ]);
    }

    // ログイン中の再確認ページ（未同意の人向け）
    public function review(Request $request)
    {
        $user = $request->user();
        $accepted = $user?->terms_accepted_version === config('app.terms_version', '2025-09-28');

        return Inertia::render('Public/Terms', [
            'version'  => config('app.terms_version', '2025-09-28'),
            'mode'     => 'review',
            'accepted' => $accepted,
        ]);
    }

    // 同意ボタン
    public function accept(Request $request)
    {
        $user = $request->user();
        $user->forceFill([
            'terms_accepted_version' => config('app.terms_version', '2025-09-28'),
            'terms_accepted_at'      => now(),
        ])->save();

        return redirect()->intended('/dashboard')->with('success','利用規約に同意しました。');
    }
}
