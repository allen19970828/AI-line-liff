<?php

namespace App\Filament\Resources\Schools\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchoolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // [第一卷：欄位佈局 - 視覺優化]
            ->columns([
                // 1. 核心識別：代碼
                // 宗師指點：加上 copyable()，客服查案時一鍵複製，不用手抄。
                TextColumn::make('code')
                    ->label('學校代碼')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('已複製代碼')
                    ->searchable()
                    ->sortable(),

                // 2. 學校資訊：名稱 + 統編
                // 宗師指點：利用 description 將次要資訊 (統編) 藏於下方，節省橫向空間。
                TextColumn::make('name')
                    ->label('學校名稱')
                    ->description(
                        fn (Model $record): string => $record->tax_id ? "統編: {$record->tax_id}" : ''
                    )
                    ->searchable(['name', 'tax_id']) // 讓這裡也能搜到統編
                    ->wrap(), // 允許換行，避免長名稱撐爆表格

                // 3. 地理位置：合併顯示
                // 宗師指點：縣市與行政區分開佔兩欄太浪費，合體為一欄。
                TextColumn::make('city')
                    ->label('地區')
                    ->formatStateUsing(fn (Model $record) => "{$record->city} {$record->district}")
                    ->badge() // 用徽章樣式，視覺上更整齊
                    ->color('gray')
                    ->sortable(),

                // 4. 聯絡方式：圖示化
                TextColumn::make('phone')
                    ->label('電話')
                    ->icon('heroicon-m-phone')
                    ->searchable()
                    ->toggleable(), // 可隱藏

                // 5. 網站：可點擊
                TextColumn::make('website')
                    ->label('官網')
                    ->icon('heroicon-m-globe-alt')
                    ->url(fn ($state) => $state, shouldOpenInNewTab: true) // 點擊直接開新視窗
                    ->formatStateUsing(fn () => '前往網站') // 不顯示冗長的 URL，只顯示文字
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true), // 預設隱藏，保持介面清爽

                // 6. 狀態：紅綠燈
                IconColumn::make('is_active')
                    ->label('營運狀態')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                // 7. 時空標記
                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // [第二卷：過濾心法]
            ->filters([
                // 軟刪除過濾器
                TrashedFilter::make(),

                // 狀態過濾器
                SelectFilter::make('is_active')
                    ->label('營運狀態')
                    ->options([
                        true => '營運中',
                        false => '停用/審核中',
                    ]),

                // 地區過濾器 (進階)
                SelectFilter::make('city')
                    ->label('縣市')
                    ->options(fn () => \App\Models\School::distinct()->pluck('city', 'city')->filter()->toArray())
                    ->searchable(),
            ])

            // [第三卷：操作手段]
            // 宗師修正：Table 的 Action 在 Filament v4 統一引用 Filament\Actions 下的類別
            ->actions([
                EditAction::make()
                    ->iconButton(), // 用小按鈕，省空間
                DeleteAction::make()
                    ->iconButton(),
                RestoreAction::make()
                    ->iconButton(),
            ])

            // 宗師修正：Bulk Actions 應該放在 bulkActions() 方法中，而非 toolbarActions
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                    // 宗師加持：批次更新營運狀態
                    \Filament\Actions\BulkAction::make('updateStatus')
                        ->label('更新營運狀態')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Select::make('is_active')
                                ->label('設定狀態')
                                ->options([
                                    true => '營運中',
                                    false => '停止營運',
                                ])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            // 宗師心法：使用 each 逐一更新，觸發 model event (若有)
                            $records->each(fn ($record) => $record->update(['is_active' => $data['is_active']]));

                            // 通知
                            \Filament\Notifications\Notification::make()
                                ->title('更新完成')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])

            // 若表格資料為空時的提示
            ->emptyStateHeading('尚無學校資料')
            ->emptyStateDescription('請點擊上方按鈕新增第一所學校。')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
