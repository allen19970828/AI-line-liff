<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// LIFF Entry Point
Route::get('/liff', function () {
    return view('liff.index', [
        'liffId' => config('services.line.liff_id'),
    ]);
})->name('liff.index');

// LINE Socialite Login
Route::get('/login/line', [\App\Http\Controllers\LineLoginController::class, 'redirect'])->name('login.line');
Route::get('/login/line/callback', [\App\Http\Controllers\LineLoginController::class, 'callback']);

Route::middleware(['auth'])->group(function () {
    Route::get('/profile/complete', [\App\Http\Controllers\ProfileCompletionController::class, 'edit'])->name('profile.complete');
    Route::post('/profile/complete', [\App\Http\Controllers\ProfileCompletionController::class, 'update'])->name('profile.update');
});
