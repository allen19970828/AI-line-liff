<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\LineIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_liff_login_binding_and_meal_cancellation_flow()
    {
        // 1. Setup Data
        $school = School::factory()->create(['code' => 'TEST001']);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'name' => 'Test Student',
            'student_code' => 'S1234567',
        ]);

        // Mock Socialite User
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('LINE12345');
        $socialiteUser->shouldReceive('getName')->andReturn('LINE User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('line_user@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
        $socialiteUser->shouldReceive('getRaw')->andReturn([]);
        $socialiteUser->token = 'mock_token';
        $socialiteUser->refreshToken = 'mock_refresh_token';
        $socialiteUser->expiresIn = 3600;

        // 2. Mock Service to return User directly (bypassing HTTP request to LINE)
        // Isolate: Create User via Service first
        $service = app(LineIdentityService::class);
        $user = $service->handleSocialiteCallback($socialiteUser);

        // Act as this user
        $this->actingAs($user);

        // 3. Test Binding
        $response = $this->postJson('/api/bind', [
            'code' => 'TEST001',
            'real_name' => 'Real Teacher',
            'staff_role' => 'teacher',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('school_name', $school->name);

        $this->assertEquals($school->id, $user->refresh()->school_id);
        $this->assertDatabaseHas('school_staff', [
            'user_id' => $user->id,
            'name' => 'Real Teacher',
            'school_id' => $school->id,
        ]);

        // 4. Test Meal Cancellation
        // Ensure student belongs to school (factory did this)

        $date = now()->addDays(3)->toDateString(); // Future date

        $response = $this->postJson('/api/meal-cancellation', [
            'student_id' => $student->id,
            'date' => $date,
            'leave_type' => 'personal',
            'reason' => 'Testing API',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('meal_cancel_requests', [
            'student_id' => $student->id,
            'start_date' => $date.' 00:00:00', // SQLite saves date as datetime string
            'leave_type' => 'personal',
            'status' => 'pending',
            'origin' => 'liff',
        ]);
    }
}
