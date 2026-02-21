<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @property \App\Models\School $school
 * @property \App\Models\Semester $semester
 * @property \App\Models\Classroom $classroom
 * @property \App\Models\User $teacher
 * @property \App\Models\Staff $teacherStaff
 * @property \App\Models\User $admin
 * @property \App\Models\Staff $adminStaff
 * @property \Illuminate\Support\Collection $students
 * @property \App\Services\MealService $service
 */
abstract class TestCase extends BaseTestCase
{
    //
}
