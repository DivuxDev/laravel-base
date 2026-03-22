<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health check — used by Docker/Coolify HEALTHCHECK
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

/*
|--------------------------------------------------------------------------
| Public routes — no authentication required
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register')
        ->middleware('throttle:register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login')
        ->middleware('throttle:10,1'); // 10 attempts per minute

    // Google OAuth
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    // Password reset
    Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword'])
        ->middleware('throttle:password-reset')
        ->name('auth.forgot-password');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:password-reset')
        ->name('auth.reset-password');

    // Email verification (public — users click link from email before logging in)
    Route::post('/verify-email', [VerifyEmailController::class, 'verify'])
        ->name('auth.verify-email');
});

/*
|--------------------------------------------------------------------------
| Protected routes — require Sanctum token
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user',         [AuthController::class, 'user'])->name('auth.user')
        ->middleware('throttle:user-api');

    Route::put('/user/profile', [ProfileController::class, 'update'])->name('user.profile.update');

    // Email verification (resend — requires auth)
    Route::post('/auth/verify-email/send', [VerifyEmailController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('auth.verify-email.send');

    /*
    |----------------------------------------------------------------------
    | Admin routes — require Sanctum token + admin role
    |----------------------------------------------------------------------
    */
    Route::middleware(['admin', 'throttle:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get   ('/users',                      [AdminUserController::class, 'index'])         ->name('users.index');
        Route::patch ('/users/{id}/role',            [AdminUserController::class, 'changeRole'])    ->name('users.role');
        Route::post  ('/users/{id}/reset-password',  [AdminUserController::class, 'resetPassword']) ->name('users.reset-password');
        Route::delete('/users/{id}',                 [AdminUserController::class, 'destroy'])       ->name('users.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
