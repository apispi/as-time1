<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home');
Route::view('/docs', 'docs')->name('docs');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

    // Throttled per IP: these are the two endpoints that take a password.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
    });

    // /auth/google/… and /auth/apispi/… — the callback URL registered with
    // each provider must match exactly.
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', array_keys(SocialAuthController::PROVIDERS))->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', array_keys(SocialAuthController::PROVIDERS))->name('social.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:3,1')->name('verification.send');

    // Password sign-ups get in only after confirming their email.
    Route::middleware('verified')->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
        Route::redirect('/account', '/dashboard');

        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
            Route::get('/', [AdminController::class, 'overview'])->name('overview');
            Route::get('/users', [AdminController::class, 'users'])->name('users');
            Route::post('/users/{user}/admin', [AdminController::class, 'toggleAdmin'])->name('users.admin');
            Route::post('/users/{user}/verify', [AdminController::class, 'verify'])->name('users.verify');
            Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
            Route::get('/system', [AdminController::class, 'system'])->name('system');
        });
    });
});
