<?php

namespace App\Filament\Resources\NationalHolidays\Widgets;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;

class SyncNationalHolidaysWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.resources.national-holidays.widgets.sync-national-holidays-widget';

    protected int|string|array $columnSpan = 'full';

    public ?int $year = null;

    public function mount(): void
    {
        $this->form->fill([
            'year' => now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('year')
                    ->label('同步年份')
                    ->numeric()
                    ->default(now()->year)
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public ?array $data = [];

    public function sync(): void
    {
        $data = $this->form->getState();
        $year = $data['year'];

        try {
            Artisan::call('sync:holidays', ['year' => $year]);

            Notification::make()
                ->title('同步成功')
                ->body("已成功同步 {$year} 年的國定假日資料。")
                ->success()
                ->send();

            // 重新整理頁面以顯示新資料
            $this->dispatch('refresh-national-holidays-list');

        } catch (\Exception $e) {
            Notification::make()
                ->title('同步失敗')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
