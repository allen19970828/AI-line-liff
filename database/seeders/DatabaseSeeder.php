<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 基礎權限
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. 匯入真實資料 (可透過 ENV 控制開關，開發時設為 false 可加速)
        if (env('SEED_REAL_SCHOOLS', true)) {
            $this->call(SchoolSeeder::class);
        } else {
            $this->command->warn('⏩ 跳過真實學校匯入 (開發模式)');
        }

        $this->call(TaiwanHolidaysSeeder::class);

        // 3. 建立虛擬學校 (必定執行)
        $this->command->info('🏫 建立示範學校...');
        $demoSchool = School::updateOrCreate(
            ['code' => 'TEST001'],
            [
                'name' => '系統測試小學',
                'city' => '高雄市',
                'district' => '前鎮區',
                'address' => '雲端之上',
                'phone' => '07-0000000',
                'is_active' => true,
            ]
        );

        // 4. 建立 Admin 並綁定
        $this->command->info('👤 建立超級管理員...');
        $admin = User::updateOrCreate(
            ['email' => 'admin@deepmatrix.com'],
            [
                'name' => 'Grandmaster Yu',
                'password' => Hash::make('root'),
                'email_verified_at' => now(),
                'school_id' => $demoSchool->id, // 綁定主要學校
            ]
        );

        // [多租戶綁定] 如果有使用 Filament Shield 或多對多關聯
        if (method_exists($admin, 'schools')) {
            $admin->schools()->syncWithoutDetaching([$demoSchool->id]);
        }

        // 給予最高權限 (假設你用的是 Spatie Permission 的 Super Admin 邏輯)
        // 給予最高權限 (宗師補丁：已在 RolesAndPermissionsSeeder 建立角色)
        $admin->assignRole('super_admin');

        // 5. 生成虛擬學校內部的資料 (Staff, Classrooms)
        $this->call(DemoDataSeeder::class);

        // --- 結束畫面 ---
        $this->command->info('---------------------------------------');
        $this->command->info('✅ 系統初始化完成！');
        $this->command->info('👉 登入帳號: admin@deepmatrix.com');
        $this->command->info('👉 登入密碼: root');
        $this->command->info("👉 所在學校: {$demoSchool->name}");
        $this->command->info('---------------------------------------');
    }
}
