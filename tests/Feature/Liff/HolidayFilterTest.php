<?php

use App\Models\NationalHoliday;
use App\Models\Semester;
use App\Models\SemesterException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access holidays', function () {
    $response = $this->getJson('/api/cancellation/holidays');
    $response->assertStatus(401);
});

test('authenticated user can get national holidays', function () {
    $user = User::factory()->create();

    // 1. 建立未來幾天的國定假日
    NationalHoliday::create([
        'date' => now()->addDays(2),
        'name' => '國慶日',
        'type' => NationalHoliday::TYPE_HOLIDAY,
    ]);

    // 2. 建立補班日 (不應該出現在 disabled list)
    NationalHoliday::create([
        'date' => now()->addDays(3), // 假設剛好是週末
        'name' => '補班日',
        'type' => NationalHoliday::TYPE_MAKEUP,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/cancellation/holidays');

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'holidays'])
        ->assertJsonFragment([
            'status' => 'success',
        ]);

    // 驗證國慶日在清單中
    $holidays = $response->json('holidays');
    expect($holidays)->toHaveKey(now()->addDays(2)->toDateString());

    // 驗證補班日不在清單中
    expect($holidays)->not->toHaveKey(now()->addDays(3)->toDateString());
});

test('authenticated user can get school exceptions', function () {
    $user = User::factory()->create();

    // 1. 建立學校與學期
    $school = \App\Models\School::factory()->create();
    $user->update(['school_id' => $school->id]);

    $semester = Semester::factory()->create([
        'school_id' => $school->id,
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(2),
    ]);

    // 2. 建立校園例外 (校慶補假 - 不供餐)
    SemesterException::create([
        'semester_id' => $semester->id,
        'date' => now()->addDays(5),
        'description' => '校慶補假',
        'is_meal_day' => false,
    ]);

    // 3. 建立校園例外 (運動會 - 供餐)
    SemesterException::create([
        'semester_id' => $semester->id,
        'date' => now()->addDays(6),
        'description' => '運動會',
        'is_meal_day' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/cancellation/holidays');

    $response->assertStatus(200);

    $holidays = $response->json('holidays');

    // 校慶補假 (不供餐) 應出現在清單中
    expect($holidays)->toHaveKey(now()->addDays(5)->toDateString());

    // 運動會 (供餐) 不應出現在清單中
    expect($holidays)->not->toHaveKey(now()->addDays(6)->toDateString());
});
