<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes — no authentication required
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login')
        ->middleware('throttle:10,1'); // 10 attempts per minute

    // Google OAuth
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

/*
|--------------------------------------------------------------------------
| Protected routes — require Sanctum token
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user',         [AuthController::class, 'user'])->name('auth.user');

    /*
    |----------------------------------------------------------------------
    | Admin routes — require Sanctum token + admin role
    |----------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get   ('/users',                      [AdminUserController::class, 'index'])         ->name('users.index');
        Route::patch ('/users/{id}/role',            [AdminUserController::class, 'changeRole'])    ->name('users.role');
        Route::post  ('/users/{id}/reset-password',  [AdminUserController::class, 'resetPassword']) ->name('users.reset-password');
        Route::delete('/users/{id}',                 [AdminUserController::class, 'destroy'])       ->name('users.destroy');
    });
});
