<x-filament-panels::page>
    <div class="flex flex-col gap-6" x-data
        @open-print-window.window="window.open($event.detail.url, '_blank').focus()">

        <!-- 控制面板 (僅螢幕顯示) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 dark:bg-gray-900 dark:border-gray-800">
            <div class="mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">
                <h3 class="text-lg font-bold">通知單內容設定</h3>
            </div>
            {{ $this->form }}
            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="$refresh">
                    設定重設與儲存
                </x-filament::button>
                <x-filament::button icon="heroicon-o-printer" size="lg" x-on:click="
                    let win = window.open('', '_blank');
                    $wire.preparePrint().then(url => {
                        if(url) {
                            win.location.href = url;
                        } else {
                            win.close();
                        }
                    });
                ">
                    立即列印 (A4)
                </x-filament::button>
            </div>
        </div>

    </div>
</x-filament-panels::page>