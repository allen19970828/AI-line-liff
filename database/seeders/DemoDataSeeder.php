<?php

namespace Database\Seeders;

use App\Enums\DietaryType;
use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
use App\Enums\StaffRole;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 鎖定我們的「系統測試小學」 (DatabaseSeeder 中建立的 code 是 TEST001)
        $school = School::where('code', 'TEST001')->first();

        if (!$school) {
            $this->command->warn('⚠️ 找不到測試學校 (TEST001)，跳過 Demo Data 生成。');

            return;
        }

        $this->command->info("🏗️ [虛擬世界] 正在為 {$school->name} 建立教職員與班級...");

        // 2. 建立教職員 (涵蓋各種情境：全職/兼職/葷/素)
        $staffData = [
            [
                'name' => '王校長',
                'job' => '校長',
                'stage' => SemesterStage::Elementary,
                'staff_role' => StaffRole::Admin,
                'diet' => DietaryType::Meat,
                'meal_user' => true,
            ],
            [
                'name' => '李教務',
                'job' => '教務主任',
                'stage' => SemesterStage::Elementary,
                'staff_role' => StaffRole::Admin,
                'diet' => DietaryType::Meat,
                'meal_user' => true,
            ],
            [
                'name' => '陳大明', // 一年甲班導師
                'job' => '教師',
                'stage' => SemesterStage::Elementary,
                'staff_role' => StaffRole::Teacher,
                'diet' => DietaryType::Meat,
                'meal_user' => true,
            ],
            [
                'name' => '林小美', // 幼兒園導師 (素食)
                'job' => '教保員',
                'stage' => SemesterStage::Kindergarten,
                'staff_role' => StaffRole::Teacher,
                'diet' => DietaryType::Vegan,
                'meal_user' => true,
            ],
            [
                'name' => '張阿姨', // 廚工 (不吃)
                'job' => '廚工',
                'stage' => SemesterStage::Elementary,
                'staff_role' => StaffRole::Worker,
                'diet' => DietaryType::Meat,
                'meal_user' => false,
            ],
        ];

        foreach ($staffData as $index => $data) {
            Staff::updateOrCreate(
                ['school_id' => $school->id, 'name' => $data['name']],
                [
                    'lunch_code' => 'lunch' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'job_title' => $data['job'],
                    'stage' => $data['stage'],
                    'staff_role' => $data['staff_role'],
                    'dietary_type' => $data['diet'],
                    'is_meal_user' => $data['meal_user'],
                    'meal_days' => $data['meal_user'] ? [1, 2, 3, 4, 5] : null,
                    'is_active' => true,
                ]
            );
        }

        // 3. 建立班級 (測試排序與學制)
        $teacherElem = Staff::where('name', '陳大明')->first();
        $teacherKind = Staff::where('name', '林小美')->first();

        $classrooms = [
            [
                'stage' => SemesterStage::Kindergarten,
                'grade' => GradeLevel::KINDERGARTEN_SMALL,
                'name' => '太陽班',
                'teacher_id' => $teacherKind?->id,
            ],
            [
                'stage' => SemesterStage::Elementary,
                'grade' => GradeLevel::GRADE_1,
                'name' => '甲班',
                'teacher_id' => $teacherElem?->id,
            ],
            [
                'stage' => SemesterStage::Elementary,
                'grade' => GradeLevel::GRADE_1,
                'name' => '乙班',
                'teacher_id' => null,
            ],
            [
                'stage' => SemesterStage::Elementary,
                'grade' => GradeLevel::GRADE_6,
                'name' => '忠班',
                'teacher_id' => null,
            ],
        ];

        foreach ($classrooms as $cls) {
            Classroom::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'stage' => $cls['stage'],
                    'grade' => $cls['grade'],
                    'class_name' => $cls['name'],
                ],
                [
                    'school_staff_id' => $cls['teacher_id'],
                    'teacher_meal_qty' => $cls['teacher_id'] ? 1 : 0,
                    'class_type' => 'general',
                    'location' => 'A棟1F',
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ 虛擬資料生成完畢！');
    }
}
