<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SystemSelfHealingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:self-healing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '系統自動化檢修與自我修復指令';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('['.now()->toDateTimeString().'] 開始執行系統自動檢修...');
        Log::channel('daily')->info('System Self-Healing started.');

        // 1. 檢查 Storage Symlink (Zeabur 環境優先)
        $this->checkAndFixStorageLink();

        // 2. 檢查必要環境變數
        $this->checkEnvironmentVariables();

        // 3. 檢查資料庫狀態 (是否有未跑的 Migration)
        $this->checkDatabaseMigrations();

        // 4. 觸發學校資料修補 (checkin_code)
        $this->call('school:repair');

        $this->info('檢修完成。');
        Log::channel('daily')->info('System Self-Healing completed.');

        return 0;
    }

    /**
     * 檢查並檢查 Storage 軟連結
     */
    protected function checkAndFixStorageLink(): void
    {
        $publicStorage = public_path('storage');
        $isZeabur = file_exists('/data');
        $target = $isZeabur ? '/data/public' : storage_path('app/public');

        if ($isZeabur && ! File::exists('/data/public')) {
            File::makeDirectory('/data/public', 0755, true);
            $this->warn(' - 已自動建立 /data/public 目錄');
        }

        if (! File::exists($publicStorage) || (is_link($publicStorage) && readlink($publicStorage) !== $target)) {
            $this->warn(' - 發現 Storage Symlink 異常，正在修復...');

            if (File::exists($publicStorage) || is_link($publicStorage)) {
                // 在本地環境如果是目錄則刪除目錄，如果是連結則刪除連結
                File::delete($publicStorage);
            }

            try {
                if ($isZeabur) {
                    // Zeabur 環境使用 ln -s
                    exec("ln -sf {$target} {$publicStorage}");
                } else {
                    Artisan::call('storage:link');
                }
                $this->info(' - Storage Symlink 修復成功！');
                Log::info('Self-Heal: Fixed storage symlink.');
            } catch (\Exception $e) {
                $this->error(' - Storage Symlink 修復失敗：'.$e->getMessage());
            }
        } else {
            $this->line(' - Storage Symlink 正常。');
        }
    }

    /**
     * 檢查關鍵環境變數
     */
    protected function checkEnvironmentVariables(): void
    {
        $criticalKeys = [
            'STORAGE_PATH_PUBLIC' => '用於持久化存儲圖片',
            'SUPER_ADMIN_EMAILS' => '用於管理員上帝視角權限',
            'LINE_CLIENT_ID' => '用於 LINE 登入',
        ];

        foreach ($criticalKeys as $key => $desc) {
            if (empty(env($key))) {
                $msg = "⚠️ 缺失環境變數: {$key} ({$desc})，可能會導致部分功能異常。";
                $this->warn(' - '.$msg);
                Log::warning('Self-Heal: '.$msg);
            }
        }
    }

    /**
     * 檢查是否有待執行的 Migration
     */
    protected function checkDatabaseMigrations(): void
    {
        try {
            Artisan::call('migrate:status');
            $status = Artisan::output();

            if (str_contains($status, 'Pending')) {
                $this->warn(' - 發現有待執行的資料庫遷移 (Migrations)。');
                Log::warning('Self-Heal: Pending migrations detected.');

                // 在 Zeabur 可以嘗試自動執行 (但通常 pre_start 已經跑過)
                // $this->call('migrate', ['--force' => true]);
            } else {
                $this->line(' - 資料庫遷移狀態正常。');
            }
        } catch (\Exception $e) {
            $this->error(' - 無法檢查資料庫遷移狀態：'.$e->getMessage());
        }
    }
}
