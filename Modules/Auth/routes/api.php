<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\GoogleOAuthController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Auth\Http\Controllers\ProfileController;
use Modules\Auth\Http\Controllers\EmailChangeController;
use Modules\Auth\Http\Controllers\MobileChangeController;

Route::prefix('auth')->name('auth.')->group(function () {
    // Test route
    Route::get('test', function() {
        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Auth module is working!',
            'server_time' => now()->toISOString(),
        ]);
    })->name('test');

    // Login routes (بدون rate limiter موقتاً)
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::post('login/code', [LoginController::class, 'code'])->name('login.code');

    // Register routes (بدون rate limiter موقتاً)
    Route::post('register', [RegisterController::class, 'register'])->name('register');
    Route::post('register/code', [RegisterController::class, 'code'])->name('register.code');

    // Google OAuth routes
    Route::prefix('google')->name('google.')->group(function () {
        Route::get('redirect', [GoogleOAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [GoogleOAuthController::class, 'callback'])->name('callback');
        Route::post('callback', [GoogleOAuthController::class, 'handleCallback'])->name('api-callback');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('unlink', [GoogleOAuthController::class, 'unlink'])->name('unlink');
        });
    });
});

Route::middleware('auth:sanctum')->name('user.')->prefix('user')->group(function () {
    Route::get('logout', [ProfileController::class, 'logout'])->name('logout');
    Route::get('show', [ProfileController::class, 'show'])->name('show');
    Route::post('update', [ProfileController::class, 'update'])->name('update');
    Route::post('upload-avatar', [ProfileController::class, 'uploadAvatar'])->name('upload');

    // Mobile change routes
    Route::post('mobile-change/code', [MobileChangeController::class, 'send_code_current_mobile'])->name('mobile.change.code');
    Route::post('mobile-change/verify', [MobileChangeController::class, 'verify_current_mobile'])->name('mobile.change.verify');
    Route::post('mobile-change/new/code', [MobileChangeController::class, 'send_code_new_mobile'])->name('mobile.change.new_send');
    Route::post('mobile-change/new/verify', [MobileChangeController::class, 'verify_new_mobile'])->name('mobile.change.new_verify');

    // Email change routes
    Route::post('email-change/code', [EmailChangeController::class, 'send_code_current_email'])->name('email.change.code');
    Route::post('email-change/verify', [EmailChangeController::class, 'verify_current_email'])->name('email.change.verify');
    Route::post('email-change/new/code', [EmailChangeController::class, 'send_code_new_email'])->name('email.change.new_send');
    Route::post('email-change/new/verify', [EmailChangeController::class, 'verify_new_email'])->name('email.change.new_verify');
});
