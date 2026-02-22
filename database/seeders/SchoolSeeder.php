<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // 這裡預留給匯入真實學校資料的邏輯
        // 目前先建立一個示範用的高雄市學校
        School::updateOrCreate(
            ['code' => 'TEST001'],
            [
                'name' => '系統示範國小',
                'city' => '高雄市',
                'district' => '前鎮區',
                'address' => '一心一路 1 號',
                'phone' => '07-1234567',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ 真實學校資料 (示範) 匯入完畢！');
    }
}
