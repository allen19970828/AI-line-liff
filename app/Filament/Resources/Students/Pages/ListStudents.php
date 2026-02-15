<?php

namespace App\Filament\Resources\Students\Pages;

use App\Exports\StudentTemplateExport;
use App\Imports\StudentsImport;
use App\Services\PromotionService; // 引入升級服務
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListStudents extends ListRecords
{
    protected static string $resource = \App\Filament\Resources\Students\StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. [核彈級] 全校學年升級 (移駕至此)
            Actions\Action::make('promoteAll')
                ->label('全校學年升級')
                ->icon('heroicon-o-rocket-launch') // 發射！
                ->color('danger') // 紅色警戒
                // 權限控管：建議只有校長或系統管理員能看見
                // ->visible(fn () => auth()->user()->hasRole('admin'))

                ->requiresConfirmation()
                ->modalHeading('⚠️ 確定要執行「新學年升級」嗎？')
                ->modalDescription(new \Illuminate\Support\HtmlString(
                    '<div class="text-sm text-gray-500 space-y-2">'.
                    '<p>此操作通常於 <b>8月1日</b> 執行，將會對 <b>全校學生</b> 進行以下變更：</p>'.
                    '<ul class="list-disc pl-5 space-y-1">'.
                    '<li>🎓 <b>畢業離校</b>：六年級與大班學生將標記為畢業。</li>'.
                    '<li>🔀 <b>解散分班</b>：二年級與四年級將被移出班級 (需重新匯入分班)。</li>'.
                    '<li>📈 <b>年級晉升</b>：其餘班級自動升上高一年級。</li>'.
                    '</ul>'.
                    '<p class="text-red-600 font-bold mt-2">此操作不可復原，請確認您已備份資料庫！</p>'.
                    '</div>'
                ))
                ->modalSubmitActionLabel('是的，執行升級')
                ->action(function () {
                    $schoolId = auth()->user()->school?->id;

                    if (! $schoolId) {
                        Notification::make()->title('錯誤：找不到學校')->danger()->send();

                        return;
                    }

                    try {
                        // 呼叫你的升級服務
                        app(PromotionService::class)->performYearlyPromotion($schoolId);

                        Notification::make()
                            ->title('學年升級完成')
                            ->body('所有學生年級已晉升，請前往「班級管理」確認，並匯入新分班名單。')
                            ->success()
                            ->persistent() // 讓通知停留在畫面上，直到使用者關閉
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('升級失敗')
                            ->body("系統發生錯誤：{$e->getMessage()}")
                            ->danger()
                            ->send();
                    }
                }),

            // 2. [匯出] 下載範本
            Actions\Action::make('downloadTemplate')
                ->label('下載範本')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new StudentTemplateExport,
                    '學生匯入範本.xlsx'
                )),

            // 3. [匯入] 匯入學生 (含分班功能)
            Actions\Action::make('importStudents')
                ->label('匯入/編班')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary') // 改用 Primary 比較顯眼
                ->form([
                    FileUpload::make('attachment')
                        ->label('選擇 Excel 檔案')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // [宗師修正] 改用單數 school 關聯
                    $schoolId = auth()->user()->school?->id;

                    if (! $schoolId) {
                        Notification::make()
                            ->title('匯入失敗：找不到所屬學校')
                            ->danger()
                            ->send();

                        return;
                    }

                    // 記錄學校 ID，方便除錯
                    \Illuminate\Support\Facades\Log::info('使用者 '.auth()->id()." 正在匯入學生至學校 ID: {$schoolId}");

                    Excel::import(new StudentsImport((int) $schoolId), $data['attachment']);
                    Notification::make()->title('匯入成功')->success()->send();
                }),

            // 4. 新增單筆
            Actions\CreateAction::make(),
        ];
    }
}
