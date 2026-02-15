<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. [下載範本] 驅動 Agent 產生檔案
            \Filament\Actions\Action::make('downloadTemplate')
                ->label('下載範本')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    // 宗師指點：這裡必須要 return，Livewire 才會觸發瀏覽器下載
                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\StaffTemplateExport,
                        '教職員匯入範本_'.date('Ymd').'.xlsx'
                    );
                }),

            // 2. [匯入資料] 上傳 Excel
            \Filament\Actions\Action::make('importExcel')
                ->label('匯入教職員')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('attachment')
                        ->label('選擇 Excel 檔案')
                        ->disk('local') // 改用 local disk，確保檔案落地
                        ->directory('staff-imports') // 指定目錄
                        ->preserveFilenames() // 保留檔名，方便 debug
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // 修正路徑抓取：從 local disk 的 staff-imports 目錄抓取
                    /*
                     * 宗師指點：
                     * 你的 local disk 設定為 storage_path('app/private')
                     * 若手動用 storage_path('app/...') 會少掉 private 層
                     * 這裡改回用 Storage::disk('local')->path() 最穩
                     */
                    $file = \Illuminate\Support\Facades\Storage::disk('local')->path($data['attachment']);

                    // 修正：因為 User 與 School 是 BelongsTo 關係，直接抓 school_id
                    // 原始請求: $schoolId = auth()->user()->schools->first()->id; (會報錯)
                    $schoolId = auth()->user()->school_id;

                    if (! $schoolId) {
                        \Filament\Notifications\Notification::make()->title('錯誤')->body('找不到所屬學校')->danger()->send();

                        return;
                    }

                    \Maatwebsite\Excel\Facades\Excel::import(
                        new \App\Imports\StaffImport($schoolId),
                        $file
                    );

                    \Filament\Notifications\Notification::make()->title('匯入完成')->success()->send();
                }),

            CreateAction::make(),
        ];
    }
}
