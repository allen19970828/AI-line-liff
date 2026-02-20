<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DailyDiningCalendarWidget;
use App\Models\StaffCheckIn;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class DailyDiningPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = '教職員每日用餐統計';

    protected static ?string $title = '教職員每日用餐統計與點名';

    protected static ?string $slug = 'daily-dining-stats';

    protected string $view = 'filament.pages.daily-dining-page';

    public ?string $selectedDate = null;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    #[On('dateSelected')]
    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StaffCheckIn::query()
                    ->whereDate('checked_at', $this->selectedDate)
                    ->orderBy('checked_at', 'desc')
            )
            ->columns([
                TextColumn::make('staff.name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('staff.stage')
                    ->label('部別')
                    ->badge(),
                TextColumn::make('staff.job_title')
                    ->label('職稱'),
                TextColumn::make('checked_at')
                    ->label('簽到時間')
                    ->dateTime('H:i:s'),
            ])
            ->emptyStateHeading('這天沒有用餐紀錄喔 🍱')
            ->paginated(false);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DailyDiningCalendarWidget::class,
        ];
    }
}
