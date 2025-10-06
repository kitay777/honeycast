<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CastProfileController;
use App\Http\Controllers\CastSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CastController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReserveController;
use App\Http\Controllers\TweetController;
use App\Http\Controllers\CallRequestController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Admin\CastController as AdminCastController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\ShopInviteController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminLineController;
use App\Http\Controllers\LineRegistrationController;


// routes/web.php
use App\Http\Controllers\CastProfilePermissionController;
// routes/web.php
use App\Events\PingPong;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Auth\CastRegisterController;
use App\Http\Controllers\CastPhotoPermissionController;
use App\Http\Controllers\TermsController;
// routes/web.php
use App\Http\Controllers\LineLinkController;
use App\Http\Controllers\LineWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as VerifyCsrfTokenMiddleware;

Route::get('/register/line/complete', [LineRegistrationController::class, 'completeWithToken'])
    ->name('line.register.complete'); // ゲストOK（GETなのでCSRF不要）


/* ▼ ゲスト：登録（LIFF） */
Route::middleware('guest')->group(function () {
    Route::get('/register/line', [LineRegistrationController::class, 'page'])->name('line.register');

    // ゲスト登録は CSRF を外してもOK（使うなら withoutMiddleware を外して Vue 側で X-CSRF-TOKEN 送付）
    Route::post('/register/line/direct', [LineRegistrationController::class, 'direct'])
        ->name('line.register.direct')
        ->withoutMiddleware([VerifyCsrfTokenMiddleware::class]);
});

/* ▼ Admin */
Route::middleware(['auth','verified','can:admin'])
    ->prefix('admin')->name('admin.')->group(function () {

    Route::get('/users/{user}/line', [AdminLineController::class, 'form'])->name('users.line.form');
    Route::post('/users/{user}/line/push', [AdminLineController::class, 'push'])->name('users.line.push');
    Route::post('/line/multicast', [AdminLineController::class, 'multicast'])->name('line.multicast');

    // （重複していた invitations.respond は1つでOK）
    Route::get('/invitations/respond/{assignment}/{decision}',
        [\App\Http\Controllers\Cast\InvitationController::class, 'respondSigned'])
        ->name('invitations.respond')->middleware('signed');
});

/* ▼ LINE 連携（ログイン済み） */
Route::middleware(['auth'])->group(function () {
    // ProfileEdit のワンタップ連携は必ず auth を通す
    Route::post('/line/link/direct', [LineLinkController::class, 'direct'])->name('line.link.direct');

    Route::post('/line/link/start', [LineLinkController::class, 'start'])->name('line.link.start');
    Route::get ('/line/link/status', [LineLinkController::class,'status'])->name('line.link.status');
    Route::post('/line/push/test',   [LineLinkController::class,'pushTest'])->name('line.push.test');
    Route::delete('/line/link/disconnect', [LineLinkController::class,'disconnect'])->name('line.link.disconnect');

    // Chat：こちらだけ残す（ルート名は casts.startChat に統一）
    Route::post('/casts/{cast}/start-chat', [ChatController::class,'start'])->name('casts.startChat');
});

// ← こちらは削除：/casts/{cast}/chat/start の重複ルート
// Route::post('/casts/{cast}/chat/start', [ChatController::class, 'start'])->middleware(['auth','verified'])->name('casts.startChat');

/* ▼ 参照系（CSRF不要） */
Route::get('/line/link/peek', [LineLinkController::class, 'peek'])->name('line.link.peek');
Route::post('/line/webhook', [LineWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfTokenMiddleware::class])->name('line.webhook');

/* ▼ 既存の他ルートはそのまま（省略） */


Route::get('/terms', [TermsController::class, 'show'])->name('terms');
Route::get('/unei', [TermsController::class, 'unei'])->name('unei');
/*Route::post('/casts/{cast}/chat/start', [\App\Http\Controllers\ChatController::class, 'start'])
    ->middleware(['auth','verified'])
    ->name('casts.startChat');
*/
// ログイン中の再確認＆同意
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/terms/review',  [TermsController::class, 'review'])->name('terms.review');
    Route::post('/terms/accept', [TermsController::class, 'accept'])->name('terms.accept');
});
Route::middleware(['auth'])->group(function () {
    Route::post('/photos/{castPhoto}/unblur-requests', [CastPhotoPermissionController::class, 'store'])
        ->name('photos.unblur.request');
    Route::post('/photos/{castPhoto}/unblur-requests/{permission}/approve', [CastPhotoPermissionController::class, 'approve'])
        ->name('photos.unblur.approve');
    Route::post('/photos/{castPhoto}/unblur-requests/{permission}/deny', [CastPhotoPermissionController::class, 'deny'])
        ->name('photos.unblur.deny');
});
Route::middleware('guest')->group(function () {
    Route::get ('/register/cast', [CastRegisterController::class, 'create'])->name('cast.register');
    Route::post('/register/cast', [CastRegisterController::class, 'store'])->name('cast.register.store');
});

// 既存ユーザーが“キャスト化”だけしたい場合（任意）
Route::middleware(['auth'])->post('/cast/upgrade', [CastRegisterController::class, 'upgrade'])
    ->name('cast.upgrade');

Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

    Route::get('/chat/{thread}', [ChatController::class,'show'])
        ->name('chat.show');

    Route::post('/chat/{thread}/messages', [ChatController::class,'send'])
        ->name('chat.send');
});
Route::get('/_ping', function () {
    try {
        broadcast(new PingPong('hello via broadcasting()'));
        return response('ok', 200);
    } catch (\Throwable $e) {
        Log::error('BROADCAST ERROR', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/_pusher_raw', function () {
    $p = new \Pusher\Pusher(
        env('PUSHER_APP_KEY'),
        env('PUSHER_APP_SECRET'),
        env('PUSHER_APP_ID'),
        ['cluster' => env('PUSHER_APP_CLUSTER'), 'useTLS' => true]
    );
    $ok = $p->trigger('test', 'PingPong', ['msg' => 'hello via raw pusher']);
    return $ok ? 'raw-ok' : 'raw-ng';
});


Route::middleware(['auth'])->group(function () {
    Route::post('/casts/{castProfile}/unblur-requests', [CastProfilePermissionController::class, 'store'])
        ->name('casts.unblur.request');
    Route::post('/casts/{castProfile}/unblur-requests/{permission}/approve', [CastProfilePermissionController::class, 'approve'])
        ->name('casts.unblur.approve');
    Route::post('/casts/{castProfile}/unblur-requests/{permission}/deny', [CastProfilePermissionController::class, 'deny'])
        ->name('casts.unblur.deny');
});

Route::get('/s/{token}', [\App\Http\Controllers\Public\InviteCaptureController::class, '__invoke'])
    ->name('shop.invite.capture');

    // 👇 管理者じゃなく「オーナー」用。adminグループの“外”に置く
Route::middleware(['auth','verified','can:shop-owner'])
    ->prefix('my')->name('my.')
    ->group(function () {
        Route::get('/shop', [\App\Http\Controllers\Owner\PortalController::class, 'index'])
            ->name('shop');

        Route::post('/invites', [\App\Http\Controllers\Owner\InviteController::class, 'store'])
            ->name('invites.store');

        Route::get('/invites/{invite}/qr.png', [\App\Http\Controllers\Owner\InviteController::class, 'qr'])
            ->name('invites.qr');
    });
Route::middleware(['auth','verified'])
    ->prefix('cast')->name('cast.')
    ->group(function () {
        Route::get('/invitations', [\App\Http\Controllers\Cast\InvitationController::class, 'index'])
            ->name('invitations.index');

        // 画面からの応答
        Route::put('/invitations/{assignment}/accept',  [\App\Http\Controllers\Cast\InvitationController::class, 'accept'])
            ->name('invitations.accept');
        Route::put('/invitations/{assignment}/decline', [\App\Http\Controllers\Cast\InvitationController::class, 'decline'])
            ->name('invitations.decline');

        // ★ メール1クリック応答（署名付きURL）
        Route::get('/invitations/respond/{assignment}/{decision}',
            [\App\Http\Controllers\Cast\InvitationController::class, 'respondSigned'])
            ->name('invitations.respond')
            ->middleware('signed');
    });
Route::middleware(['auth','verified','can:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get   ('/ng-words',        [\App\Http\Controllers\Admin\NgWordController::class,'index'])->name('ng.index');
        Route::post  ('/ng-words',        [\App\Http\Controllers\Admin\NgWordController::class,'store'])->name('ng.store');
        Route::put   ('/ng-words/{word}', [\App\Http\Controllers\Admin\NgWordController::class,'update'])->name('ng.update');
        Route::delete('/ng-words/{word}', [\App\Http\Controllers\Admin\NgWordController::class,'destroy'])->name('ng.destroy');
    
        Route::get('/tags', [\App\Http\Controllers\Admin\TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [\App\Http\Controllers\Admin\TagController::class, 'store'])->name('tags.store');
        Route::put('/tags/{tag}', [\App\Http\Controllers\Admin\TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [\App\Http\Controllers\Admin\TagController::class, 'destroy'])->name('tags.destroy');        
        
        // ★ メール内の1クリック応答（署名付き）
        Route::get('/invitations/respond/{assignment}/{decision}',
            [\App\Http\Controllers\Cast\InvitationController::class, 'respondSigned'])
            ->name('invitations.respond')   // ← これ！(cast. + invitations.respond)
            ->middleware('signed');
        // メールの「1クリック応答」用（署名付き）
        Route::get('/invitations/respond/{assignment}/{decision}', [\App\Http\Controllers\Cast\InvitationController::class, 'respondSigned'])
        ->name('invitations.respond')->middleware('signed');
        Route::get('/invite-logs', [\App\Http\Controllers\Admin\InviteUsageController::class, 'index'])->name('invites.logs');
         // Cast 管理（既存）
        Route::get   ('/casts',         [\App\Http\Controllers\Admin\CastController::class, 'index'])->name('casts.index');
        Route::post  ('/casts',         [\App\Http\Controllers\Admin\CastController::class, 'store'])->name('casts.store');
        Route::put   ('/casts/{cast}',  [\App\Http\Controllers\Admin\CastController::class, 'update'])->name('casts.update');
        Route::delete('/casts/{cast}',  [\App\Http\Controllers\Admin\CastController::class, 'destroy'])->name('casts.destroy');

        // User 管理（新規）
        Route::get   ('/users',         [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::post  ('/users',         [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        Route::put   ('/users/{user}',  [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',  [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
        Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');
        Route::put('/shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
        Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');

        // 招待発行 & QR
        Route::post('/shops/{shop}/invites', [ShopInviteController::class, 'store'])->name('shops.invites.store');
        Route::get ('/invites/{invite}/qr.png', [ShopInviteController::class, 'qr'])->name('invites.qr'); // 画像
        Route::delete('/invites/{invite}', [ShopInviteController::class, 'destroy'])->name('invites.destroy');
    
        Route::get   ('/schedules',       [\App\Http\Controllers\Admin\CastShiftController::class, 'index'])->name('schedules.index');
        Route::post  ('/schedules',       [\App\Http\Controllers\Admin\CastShiftController::class, 'store'])->name('schedules.store');
        Route::put   ('/schedules/{shift}', [\App\Http\Controllers\Admin\CastShiftController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{shift}', [\App\Http\Controllers\Admin\CastShiftController::class, 'destroy'])->name('schedules.destroy');
    
        // 一覧 & 詳細
        Route::get('/requests', [\App\Http\Controllers\Admin\CallRequestController::class, 'index'])->name('requests.index');

        // 割当・解除・ステータス変更
        Route::post  ('/requests/{req}/assign', [\App\Http\Controllers\Admin\CallRequestController::class, 'assign'])->name('requests.assign');
        Route::delete('/requests/{req}/assign/{assignment}', [\App\Http\Controllers\Admin\CallRequestController::class, 'unassign'])->name('requests.unassign');
        Route::put   ('/requests/{req}/status', [\App\Http\Controllers\Admin\CallRequestController::class, 'updateStatus'])->name('requests.status');
    
    
    });



Route::middleware(['auth','verified'])->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');            // 一覧
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show'); // 詳細
    Route::post('/messages/{conversation}', [MessageController::class, 'store'])->name('messages.store'); // 送信
    // まだ会話が無い相手と開始する場合
    Route::post('/messages/start', [MessageController::class, 'start'])->name('messages.start');
});

Route::middleware(['auth','verified'])
    ->get('/reservations', [ReservationController::class, 'index'])
    ->name('reservations.index');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/call',  [CallRequestController::class, 'create'])->name('call.create');
    Route::post('/call', [CallRequestController::class, 'store'])->name('call.store');
    Route::get('/call/{callRequest}', [CallRequestController::class, 'show'])->name('call.show');
});
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/tweets', [TweetController::class,'index'])->name('tweets.index');
    Route::get('/tweet', [TweetController::class,'index'])->name('tweets.index');
    Route::get('/tweets/create', [TweetController::class,'create'])->name('tweets.create');
    Route::post('/tweets', [TweetController::class,'store'])->name('tweets.store');
});

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/reserve', [ReserveController::class, 'create'])->name('reserve');
    Route::post('/reserve', [ReserveController::class, 'store'])->name('reserve.store');
    Route::get('/reserve/{reservation}', [ReserveController::class, 'show'])
        ->name('reserve.show');
});

/*
Route::get('/reserve', function () {
    return Inertia::render('Reserve'); // ← resources/js/Pages/Reserve.vue を表示
})->name('reserve');
*/
Route::get('/system', function () {
    return Inertia::render('System');
})->name('system');

Route::get('/ranking', [RankingController::class, 'index'])
    ->middleware(['auth','verified'])
    ->name('ranking');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/casts/{cast}', [CastController::class, 'show'])->name('casts.show');

    // スケジュール編集
    Route::get('/casts/{cast}/schedule', [CastController::class, 'editSchedule'])->name('casts.schedule.edit');
    Route::post('/casts/{cast}/schedule', [CastController::class, 'updateSchedule'])->name('casts.schedule.update');
});


Route::get('/dashboard', [HomeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/search', [CastSearchController::class, 'index'])->name('cast.search');


// routes/web.php
Route::get('/age-check', function () {
    return inertia('AgeCheck');
})->name('age.check');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mypage', [CastProfileController::class, 'edit'])->name('cast.profile.edit');

    Route::get('/cast/profile/edit', [CastProfileController::class, 'edit'])->name('cast.profile.edit');
    Route::post('/cast/profile',      [CastProfileController::class, 'update'])->name('cast.profile.update');
});


Route::get('/', function () {
    /*
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
    */
    return inertia('AgeCheck');
});

/*
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
