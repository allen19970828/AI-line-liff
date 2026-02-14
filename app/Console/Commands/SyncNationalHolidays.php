<?php

namespace App\Console\Commands;

use App\Models\NationalHoliday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncNationalHolidays extends Command
{
    // 指令簡化：不再需要 school_id，因為只更新總表
    protected $signature = 'sync:holidays {year?}';

    protected $description = '從政府開放資料同步國定假日到 Master Table (不影響各校)';

    public function handle()
    {
        $year = $this->argument('year') ?? now()->year;

        // 台灣政府開放資料 API (或是你原本用的那個 JSON 來源)
        // 這裡以此 URL 為例，請替換為你穩定的來源
        $url = "https://cdn.jsdelivr.net/gh/ruyut/TaiwanCalendar/data/{$year}.json";

        $this->info("📥 正在下載 {$year} 年國定假日資料...");

        $response = Http::get($url);

        if ($response->failed()) {
            $this->error('❌ 下載失敗！請檢查網路或來源 URL。');

            return;
        }

        $data = $response->json();

        // 過濾出「放假」與「補班」
        // 來源資料結構可能不同，這裡假設是 ruyut/TaiwanCalendar 的格式
        $holidays = collect($data)
            ->filter(fn ($item) => $item['isHoliday'] === true) // 找出放假日
            ->map(function ($item) {
                // 處理日期格式 Ymd -> Y-m-d
                $date = Carbon::createFromFormat('Ymd', $item['date']);

                return [
                    'date' => $date->format('Y-m-d'),
                    // 根據 description 判斷是「放假」還是「補班」
                    // 這裡需要人工智慧一點的判斷，或者來源有欄位標示
                    'type' => $this->guessType($item['description'] ?? ''),
                    'name' => $item['description'] ?? '國定假日',
                    'year' => $date->year, // 這裡手動填，或依賴 Model 的 boot
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values()
            ->toArray();

        if (empty($holidays)) {
            $this->warn('⚠️ 該年度尚無資料或是解析失敗。');

            return;
        }

        // 寫入 Master Table
        NationalHoliday::upsert(
            $holidays,
            ['date'], // Unique Key
            ['type', 'name', 'updated_at'] // 若重複則更新這些
        );

        $this->info('✅ Master 表更新完成！共匯入 '.count($holidays).' 筆資料。');
        $this->info('💡 請提醒各校主任至後台學期設定頁面執行「匯入行事曆」。');
    }

    /**
     * 簡單猜測類型 (若 API 沒給明確欄位)
     */
    private function guessType(string $desc): string
    {
        if (str_contains($desc, '補班') || str_contains($desc, '補課') || str_contains($desc, '補行上班')) {
            return NationalHoliday::TYPE_MAKEUP;
        }

        return NationalHoliday::TYPE_HOLIDAY;
    }
}
