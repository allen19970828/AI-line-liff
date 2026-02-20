<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 1. 日曆組件 (透過 getHeaderWidgets 自動渲染，或手動在下面渲染) --}}
        {{-- 如果想控制樣式，可以手動渲染指定的 Widget --}}
        {{-- @livewire(\App\Filament\Widgets\DailyDiningCalendarWidget::class) --}}

        {{-- 2. 用餐明細表格 --}}
        <div
            class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div
                class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <x-heroicon-o-users class="w-5 h-5 text-primary-500" />
                    {{ $selectedDate }} 用餐明細
                </h3>
                <span class="text-xs text-gray-400">點擊上方日曆日期以切換內容</span>
            </div>

            <div class="p-2">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>