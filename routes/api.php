<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/liff', [\App\Http\Controllers\Api\AuthController::class, 'liffLogin']);
Route::post('/line/webhook', [\App\Http\Controllers\Api\LineBotController::class, 'handle']);

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
    Route::get('/cancellation/holidays', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'getHolidays']);
    Route::post('/cancellation/search-student', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'searchStudent']);
    Route::post('/meal-cancellation', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'store']);
    Route::post('/meal-cancellation/class-activity', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'storeClassActivity']);
    Route::post('/meal-cancellation/group', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'storeGroupCancellation']);

    // Meal Cancellation Groups
    Route::apiResource('meal-cancellation-groups', \App\Http\Controllers\Api\Liff\MealCancellationGroupController::class);

    // Student Search (for group selection)
    Route::get('/students/search', [\App\Http\Controllers\Api\Liff\MealCancellationController::class, 'searchStudents']);

    // Surveys
    Route::prefix('surveys')->group(function () {
        Route::get('current', [\App\Http\Controllers\Api\SurveyApiController::class, 'current']);
        Route::get('pending', [\App\Http\Controllers\Api\SurveyApiController::class, 'pending']);
        Route::get('history', [\App\Http\Controllers\Api\SurveyApiController::class, 'history']);
        Route::get('{survey}', [\App\Http\Controllers\Api\SurveyApiController::class, 'show']);
        Route::post('{survey}/submit', [\App\Http\Controllers\Api\SurveyApiController::class, 'submit']);
    });

    // Meal Complaints (LIFF)
    Route::get('/meal-complaints', [\App\Http\Controllers\Api\Liff\MealComplaintController::class, 'index']);
    Route::post('/meal-complaints', [\App\Http\Controllers\Api\Liff\MealComplaintController::class, 'store']);

    // Check-in (Stationary QR)
    Route::post('/check-in', [\App\Http\Controllers\Api\Liff\CheckInController::class, 'store']);

    // Settlement (LIFF)
    Route::get('/settlement/current', [\App\Http\Controllers\Api\Liff\SettlementController::class, 'getCurrent']);
    Route::get('/settlement/backfill-calendar', [\App\Http\Controllers\Api\Liff\SettlementController::class, 'getBackfillCalendar']);
    Route::post('/settlement/backfill', [\App\Http\Controllers\Api\Liff\SettlementController::class, 'submitBackfill']);
});
