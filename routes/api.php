<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/liff', [\App\Http\Controllers\Api\AuthController::class, 'liffLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Binding
    Route::post('/bind', [\App\Http\Controllers\Api\BindingController::class, 'bind']);
    Route::post('/unbind', [\App\Http\Controllers\Api\BindingController::class, 'unbind']);

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Api\BindingController::class, 'getSettings']);
    Route::post('/settings', [\App\Http\Controllers\Api\BindingController::class, 'updateSettings']);

    // Meal Cancellation (LIFF)
    Route::get('/cancellation/targets', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'getTargets']);
    Route::post('/meal-cancellation', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'store']);
});
