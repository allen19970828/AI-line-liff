<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// LIFF Entry Point
Route::get('/liff', function () {
    return view('liff.index', [
        'liffId' => config('services.line.liff_id'),
    ]);
})->name('liff.index');

Route::get('/liff/survey/{id}', [\App\Http\Controllers\Liff\SurveyController::class, 'show'])->name('liff.survey');

// LINE Socialite Login
Route::get('/login/line', [\App\Http\Controllers\LineLoginController::class, 'redirect'])->name('login.line');
Route::get('/login/line/callback', [\App\Http\Controllers\LineLoginController::class, 'callback']);

Route::middleware(['auth'])->group(function () {
    Route::get('/profile/complete', [\App\Http\Controllers\ProfileCompletionController::class, 'edit'])->name('profile.complete');
    Route::post('/profile/complete', [\App\Http\Controllers\ProfileCompletionController::class, 'update'])->name('profile.update');

    // 專用列印路由：利用 Session 將計算結果渲染於純 HTML 版面
    Route::get('/print/lunch-fee-notification', function () {
        // 先暫存，避免畫面重新整理後資料直接不見
        session()->reflash();

        $data = session('print_data');
        $calendar = session('print_calendar');
        $overview = session('print_overview');

        if (! $data || ! $calendar || ! $overview) {
            return '<h1 style="text-align:center;font-family:sans-serif;margin-top:20%;">列印資料已過期，請關閉此視窗並重新點擊列印按鈕。</h1>';
        }

        return view('print.lunch-fee-notification', compact('data', 'calendar', 'overview'));
    })->name('print.lunch-fee-notification');
});
