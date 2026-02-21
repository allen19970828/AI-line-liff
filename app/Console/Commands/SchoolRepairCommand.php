<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SchoolRepairCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:repair';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修補學校資料庫，確保所有學校都有定點報到特徵碼 (checkin_code)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schools = \App\Models\School::whereNull('checkin_code')->get();

        if ($schools->isEmpty()) {
            $this->info('所有學校皆已有特徵碼，無需修補。');

            return Command::SUCCESS;
        }

        $this->info("發現 {$schools->count()} 所學校缺失特徵碼，開始修補...");

        foreach ($schools as $school) {
            $school->checkin_code = 'chk_'.strtolower(\Illuminate\Support\Str::random(12));
            $school->save();
            $this->line(" - 修補學校: {$school->name} [{$school->checkin_code}]");
        }

        $this->info('修補完成！');

        return Command::SUCCESS;
    }
}
