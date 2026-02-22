<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportSchools extends Command
{
    /**
     * 指令名稱：在終端機輸入 php artisan import:schools 即可執行
     */
    protected $signature = 'import:schools';

    /**
     * 指令描述
     */
    protected $description = '自動偵測最新學年度，匯入全國學校資料 (國小、國中) 並清洗地址';

    /**
     * 執行主程式
     */
    public function handle()
    {
        $this->info('正在偵測教育部最新資料年份...');

        // 1. [核心心法] 自動取得可用學年 (含自動降級機制)
        $year = $this->findAvailableYear();

        if (! $year) {
            $this->error('錯誤：無法找到任何可用的學校資料來源 (教育部網站可能掛了或是結構變更)。');

            return;
        }

        $this->info("鎖定目標學年度：民國 {$year} 年");
        $this->newLine();

        // 2. 定義該學年的下載連結
        $sources = [
            '國小' => "https://stats.moe.gov.tw/files/school/{$year}/e1_new.json",
            '國中' => "https://stats.moe.gov.tw/files/school/{$year}/j1_new.json",
            // 若未來需要高中，解開下行註解即可
            // '高中' => "https://stats.moe.gov.tw/files/school/{$year}/h1_new.json",
        ];

        // 3. 逐一處理各學制
        foreach ($sources as $level => $url) {
            $this->info("正在處理 [{$level}] 資料...");
            $this->processUrl($url);
            $this->newLine();
        }

        $this->info('=== 全國學校資料匯入完成！ ===');
    }

    /**
     * 宗師心法：智慧探測年份
     * 邏輯：先算「理論年份」，若下載失敗(404)，則自動「退回去年」。
     */
    private function findAvailableYear(): ?int
    {
        // 台灣學制：8月1日換新學年
        // 若現在是 2026年 2月 => 民國 115年 => 但學年仍是 114
        // 若現在是 2026年 9月 => 民國 115年 => 學年是 115
        $currentYear = now()->year - 1911;
        if (now()->month < 8) {
            $currentYear--;
        }

        // 嘗試次數：往回找 3 年 (防止公家機關太久沒更新)
        for ($i = 0; $i < 3; $i++) {
            $testYear = $currentYear - $i;
            // 拿國小檔當作「探針」
            $testUrl = "https://stats.moe.gov.tw/files/school/{$testYear}/e1_new.json";

            $this->line("正在探測民國 {$testYear} 年...");

            // 使用 HEAD 請求極速偵測，若伺服器不支援 HEAD 則用 GET
            try {
                $response = Http::timeout(5)->head($testUrl);
                if ($response->successful()) {
                    return $testYear;
                }
                // 有些伺服器對 HEAD 會回 405，改用 GET 讀取前 1KB
                if ($response->status() === 405) {
                    if (Http::timeout(5)->get($testUrl)->successful()) {
                        return $testYear;
                    }
                }
            } catch (\Exception $e) {
                // 網路錯誤，繼續迴圈試舊年份
                continue;
            }
        }

        return null;
    }

    /**
     * 下載、清洗、寫入資料庫
     */
    private function processUrl(string $url)
    {
        try {
            $response = Http::timeout(30)->get($url);
        } catch (\Exception $e) {
            $this->error("連線失敗：{$url}");

            return;
        }

        if ($response->failed()) {
            $this->error("下載失敗 (HTTP {$response->status()})：{$url}");

            return;
        }

        // --- 資料清洗 Start ---
        $content = $response->body();

        // 宗師指點：移除 BOM (Byte Order Mark)，這是教育部 JSON 常見的隱形殺手
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON 解析失敗，檔案格式可能有誤！');

            return;
        }
        // --- 資料清洗 End ---

        $total = count($data);

        // [安全強化] 資料完整性檢查
        if ($total === 0) {
            $this->error('⚠️ 外部資料為空！可能是資料來源異常，已中止匯入以保護現有資料。');

            return;
        }

        // 檢查現有資料量，若差異超過 50% 則發出警告
        $existingCount = School::count();
        if ($existingCount > 0 && abs($total - $existingCount) / $existingCount > 0.5) {
            $this->warn("⚠️ 警告：新資料 ({$total} 筆) 與現有資料 ({$existingCount} 筆) 差異超過 50%，請人工確認。");
            if (! $this->confirm('確定要繼續匯入嗎？')) {
                $this->info('已取消匯入。');

                return;
            }
        }

        $this->line("取得 {$total} 筆資料，開始匯入...");

        // 建立進度條
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // 分批處理 (Chunk)，一次 500 筆，避免記憶體溢出
        $chunks = array_chunk($data, 500);

        foreach ($chunks as $chunk) {
            $upsertData = [];

            foreach ($chunk as $row) {
                // 清洗縣市名稱 (移除 [01] 這種前綴)
                $city = preg_replace('/^\[\d+\]/', '', $row['縣市名稱'] ?? '');

                // 清洗地址
                $rawAddress = $row['地址'] ?? '';
                $cleanAddress = preg_replace('/^\[\d+\]/', '', $rawAddress);

                // 提取行政區 (District) 邏輯
                // 例如：從 "臺中市北屯區..." 提取 "北屯區"
                $district = null;
                // 先把「縣市」從地址字串中拿掉，避免誤判
                $addressWithoutCity = str_replace($city, '', $cleanAddress);

                // 正則：抓取「區/鄉/鎮/市」結尾的字串
                if (preg_match('/^(.+?(?:區|鄉|鎮|市))/u', $addressWithoutCity, $matches)) {
                    $district = $matches[1];
                }

                // 準備寫入資料
                $upsertData[] = [
                    'code' => $row['代碼'], // Unique Key
                    'name' => $row['學校名稱'],
                    'tax_id' => null, // API 無此欄位，留空
                    'city' => $city,
                    'district' => $district,
                    'address' => $cleanAddress,
                    'phone' => $row['電話'] ?? null,
                    'website' => $row['網址'] ?? null,
                    'is_active' => true, // 匯入的學校預設啟用

                    // 時間戳記 (PostgreSQL 嚴格要求)
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($upsertData)) {
                // 使用 Upsert (不存在則新增，存在則更新)
                School::upsert(
                    $upsertData,
                    ['code'], // 這是我們在 Migration 設定的 unique key
                    [
                        // 若代碼重複，只更新以下欄位 (保護 options 和 created_at 不被覆蓋)
                        'name',
                        'city',
                        'district',
                        'address',
                        'phone',
                        'website',
                        'updated_at',
                    ]
                );

                $bar->advance(count($upsertData));
            }
        }

        $bar->finish();
        $this->newLine(); // 換行
    }
}
