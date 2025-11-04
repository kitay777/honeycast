<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemTextController;
use App\Http\Controllers\Admin\SystemTextAdminController;

Route::get('/system-texts', [SystemTextController::class, 'index']);
Route::put('/system-texts/{key}', [SystemTextController::class, 'update']);

Route::middleware(['web', 'auth', 'can:admin'])->prefix('admin')->group(function () {
    Route::get('/system-texts', [SystemTextAdminController::class, 'index']);
    Route::put('/system-texts/{key}', [SystemTextAdminController::class, 'update']);
});
