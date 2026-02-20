<?php

namespace Tests\Feature\Liff;

use App\Models\Classroom;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_cancellation_returns_correct_json_structure()
    {
        // Arrange
        dump('Creating School');
        $school = School::factory()->create();

        dump('Creating Classroom');
        // Create Classroom manually
        $classroom = Classroom::create([
            'school_id' => $school->id,
            'class_name' => '101',
            'grade' => \App\Enums\GradeLevel::GRADE_1,
            'stage' => \App\Enums\SemesterStage::Elementary,
            'is_active' => true,
        ]);

        dump('Creating Student');
        // Create Student (StudentFactory uses School factory but we can override)
        // Note: StudentFactory definition: 'school_id' => School::factory()
        // We should pass school_id and classroom_id
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'is_meal_user' => true,
        ]);

        dump('Creating User');
        $user = User::factory()->create(['school_id' => $school->id]);

        dump('Creating Staff');
        // Create Staff manually
        $staff = Staff::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'name' => 'Test Staff',
            'staff_role' => \App\Enums\StaffRole::Teacher,
            'stage' => \App\Enums\SemesterStage::Elementary,
            'dietary_type' => \App\Enums\DietaryType::Meat,
            'is_active' => true,
        ]);

        dump('Assigning Staff');
        // Assign staff to classroom (as tutor)
        $classroom->school_staff_id = $staff->id;
        $classroom->save();

        dump('Creating Semester');
        // Create Semester manually
        \App\Models\Semester::create([
            'school_id' => $school->id,
            'year' => now()->year,
            'semester' => 1,
            'name' => 'Test Semester',
            'stage' => \App\Enums\SemesterStage::Elementary,
            'staff_meal_mode' => \App\Enums\StaffMealMode::Daily,
            'meal_price' => 50,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'min_stop_days' => 1,
            'group_min_stop_days' => 1,
            'personal_stop_days' => 1,
            'sick_stop_days' => 0,
            'public_leave_days' => 3,
            'stop_cut_off_time' => '10:00:00',
            'is_active' => true,
        ]);

        dump('Sending Request');
        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/meal-cancellation', [
                'target_id' => $student->id,
                'type' => 'student',
                'start_date' => now()->addDay()->toDateString(), // Tomorrow (valid)
                'leave_type' => 'personal',
                'reason' => 'Testing',
            ]);

        dump('Received Response');
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'count', // Must be at root
                'affected_students', // Must be at root
                'data' => [
                    'name',
                    'start_date',
                    'end_date',
                ],
            ]);
    }
}
