<?php

namespace Database\Seeders;

use App\Models\NationalHoliday;
use Illuminate\Database\Seeder;

class TaiwanHolidaysSeeder extends Seeder
{
    public function run(): void
    {
        // 這裡預留給匯入台灣行政院人事行政總處行事曆的邏輯
        // 目前先手動增加一個示範假日
        NationalHoliday::updateOrCreate(
            ['date' => '2026-01-01'],
            [
                'name' => '元旦',
                'type' => 0, // 假設 0 是放假
            ]
        );

        $this->command->info('✅ 台灣假日資料 (示範) 匯入完畢！');
    }
}
