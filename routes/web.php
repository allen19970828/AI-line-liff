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
});

// 專用列印路由：利用 Cache 與 UUID 進行無權限狀態下的安全傳遞
Route::get('/print/lunch-fee-notification/{key}', function ($key) {
    if (!$payload = \Illuminate\Support\Facades\Cache::get('print_job_' . $key)) {
        return '<h1 style="text-align:center;font-family:sans-serif;margin-top:20%;">列印資料已過期，請關閉此視窗並重新點擊列印按鈕。</h1>';
    }

    $data = $payload['data'];
    $calendar = $payload['calendar'];
    $overview = $payload['overview'];

    return view('print.lunch-fee-notification', compact('data', 'calendar', 'overview'));
})->name('print.lunch-fee-notification');

// [重構] 透過 Controller 穩定列印的核心路徑
Route::post('/admin/print-lunch-fee-notification/render', \App\Http\Controllers\Admin\PrintLunchFeeController::class)
    ->middleware(['auth'])
    ->name('admin.print-lunch-fee-notification.render');

