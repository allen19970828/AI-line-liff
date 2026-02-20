<?php

use App\Enums\StaffRole;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\MealService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test school
    $this->school = School::factory()->create([
        'name' => 'Test Elementary School',
        'code' => 'TEST001',
    ]);

    // Create active semester with rules
    $this->semester = Semester::factory()->create([
        'school_id' => $this->school->id,
        'name' => '112學年度第2學期',
        'start_date' => now()->subDays(30),
        'end_date' => now()->addDays(60),
        'is_active' => true,
        'min_stop_days' => 3,
        'personal_stop_days' => 3,
        'sick_stop_days' => 1,
        'public_leave_days' => 10,
        'stop_cut_off_time' => '16:00:00',
    ]);

    // Create classroom
    $this->classroom = Classroom::factory()->create([
        'school_id' => $this->school->id,
        'name' => '一年甲班',
    ]);

    // Create homeroom teacher (導師)
    $this->teacher = User::factory()->create([
        'school_id' => $this->school->id,
        'name' => '張老師',
    ]);
    $this->teacherStaff = Staff::factory()->create([
        'user_id' => $this->teacher->id,
        'school_id' => $this->school->id,
        'staff_role' => StaffRole::Teacher,
    ]);
    $this->classroom->update(['teacher_id' => $this->teacherStaff->id]);

    // Create admin user (行政)
    $this->admin = User::factory()->create([
        'school_id' => $this->school->id,
        'name' => '林主任',
    ]);
    $this->adminStaff = Staff::factory()->create([
        'user_id' => $this->admin->id,
        'school_id' => $this->school->id,
        'staff_role' => StaffRole::Admin,
    ]);

    // Create students in the classroom
    $this->students = Student::factory()->count(5)->create([
        'school_id' => $this->school->id,
        'classroom_id' => $this->classroom->id,
    ]);

    // Initialize MealService with dependencies
    $this->service = new MealService(new \App\Services\SemesterService);
});

/* ========================================
 * Class Activity Cancellation Tests
 * ======================================== */

it('creates class activity cancellation successfully for homeroom teacher', function () {
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $count = $this->service->createClassActivityCancellation(
        classroom: $this->classroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->teacher,
        reason: '校外教學'
    );

    // 應該為 5 位學生 × 3 天 = 15 筆
    expect($count)->toBe(15);

    // 驗證資料庫
    $this->assertDatabaseHas('meal_cancel_requests', [
        'scope_type' => 'class_activity',
        'classroom_id' => $this->classroom->id,
        'requested_by' => $this->teacher->id,
        'reason' => '校外教學',
    ]);
});

it('validates class activity requires homeroom teacher permission', function () {
    // 使用非導師帳號
    $otherTeacher = User::factory()->create([
        'school_id' => $this->school->id,
    ]);
    Staff::factory()->create([
        'user_id' => $otherTeacher->id,
        'school_id' => $this->school->id,
        'staff_role' => StaffRole::Teacher,
    ]);

    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $this->service->createClassActivityCancellation(
        classroom: $this->classroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $otherTeacher,
        reason: '校外教學'
    );
})->throws(\Exception::class, '您不是該班級導師');

it('validates class activity respects 10-day lead time rule', function () {
    // 僅提前 5 天（不足 10 天）
    $startDate = now()->addDays(5)->format('Y-m-d');
    $endDate = now()->addDays(7)->format('Y-m-d');

    $this->service->createClassActivityCancellation(
        classroom: $this->classroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->teacher,
        reason: '校外教學'
    );
})->throws(\Exception::class, '需提前 10 天');

it('validates class activity respects minimum duration rule', function () {
    // 僅 2 天（不足 3 天）
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(16)->format('Y-m-d');

    $this->service->createClassActivityCancellation(
        classroom: $this->classroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->teacher,
        reason: '校外教學'
    );
})->throws(\Exception::class, '至少需達 3 天');

it('allows admin to create class activity cancellation for any classroom', function () {
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $count = $this->service->createClassActivityCancellation(
        classroom: $this->classroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->admin,
        reason: '校慶活動'
    );

    expect($count)->toBe(15);
});

/* ========================================
 * Group Cancellation Tests
 * ======================================== */

it('creates group cancellation successfully for admin', function () {
    $studentIds = $this->students->take(3)->pluck('id')->toArray();
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $count = $this->service->createGroupCancellation(
        studentIds: $studentIds,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->admin,
        reason: '運動會集訓'
    );

    // 3 位學生 × 3 天 = 9 筆
    expect($count)->toBe(9);

    // 驗證資料庫
    $this->assertDatabaseHas('meal_cancel_requests', [
        'scope_type' => 'group',
        'requested_by' => $this->admin->id,
        'reason' => '運動會集訓',
    ]);
});

it('validates group cancellation requires admin permission', function () {
    $studentIds = $this->students->take(3)->pluck('id')->toArray();
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $this->service->createGroupCancellation(
        studentIds: $studentIds,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->teacher,  // 使用一般教師帳號
        reason: '運動會集訓'
    );
})->throws(\Exception::class, '僅行政職員或午餐秘書');

it('validates group cancellation student IDs exist', function () {
    $invalidIds = [9999, 8888];  // 不存在的學生 ID
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $this->service->createGroupCancellation(
        studentIds: $invalidIds,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->admin,
        reason: '運動會集訓'
    );
})->throws(\Exception::class);

it('validates group cancellation respects lead time rule', function () {
    $studentIds = $this->students->take(3)->pluck('id')->toArray();
    $startDate = now()->addDays(5)->format('Y-m-d');  // 僅提前 5 天
    $endDate = now()->addDays(7)->format('Y-m-d');

    $this->service->createGroupCancellation(
        studentIds: $studentIds,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->admin,
        reason: '運動會集訓'
    );
})->throws(\Exception::class, '需提前 10 天');

/* ========================================
 * Edge Cases & Data Integrity
 * ======================================== */

it('records requested_by correctly for class activity', function () {
    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $this->service->createClassActivityCancellation(
        classroom: $this->classroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->teacher,
        reason: '校外教學'
    );

    // 驗證所有申請都記錄了 requested_by
    $requests = \App\Models\MealCancelRequest::where('scope_type', 'class_activity')->get();
    expect($requests)->each(function ($request) {
        expect($request->requested_by)->toBe($this->teacher->id);
    });
});

it('handles empty classroom (no students)', function () {
    $emptyClassroom = Classroom::factory()->create([
        'school_id' => $this->school->id,
        'name' => '空班級',
        'teacher_id' => $this->teacherStaff->id,
    ]);

    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $count = $this->service->createClassActivityCancellation(
        classroom: $emptyClassroom,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->teacher,
        reason: '校外教學'
    );

    expect($count)->toBe(0);
});

it('handles cross-class group cancellation correctly', function () {
    // 建立另一個班級與學生
    $anotherClassroom = Classroom::factory()->create([
        'school_id' => $this->school->id,
        'name' => '一年乙班',
    ]);
    $studentsFromClassB = Student::factory()->count(3)->create([
        'school_id' => $this->school->id,
        'classroom_id' => $anotherClassroom->id,
    ]);

    // 跨班學生 ID（甲班 2 人 + 乙班 2 人）
    $crossClassIds = [
        ...$this->students->take(2)->pluck('id')->toArray(),
        ...$studentsFromClassB->take(2)->pluck('id')->toArray(),
    ];

    $startDate = now()->addDays(15)->format('Y-m-d');
    $endDate = now()->addDays(17)->format('Y-m-d');

    $count = $this->service->createGroupCancellation(
        studentIds: $crossClassIds,
        startDate: $startDate,
        endDate: $endDate,
        requestedBy: $this->admin,
        reason: '校慶排練'
    );

    // 4 位學生 × 3 天 = 12 筆
    expect($count)->toBe(12);
});
