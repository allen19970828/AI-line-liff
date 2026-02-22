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
                <x-filament::button type="button" icon="heroicon-o-printer" size="lg" x-on:click="
                    let win = window.open('', '_blank');
                    win.document.write(`<div style='text-align:center;font-family:sans-serif;margin-top:20vh;'><h2>列印資料準備中...請稍候</h2></div>`);
                    $wire.preparePrint().then(url => {
                        if(url) {
                            win.location.href = url;
                        } else {
                            win.document.body.innerHTML = `<div style='color:red;text-align:center;font-family:sans-serif;margin-top:20vh;'><h2>缺少必要參數，請關閉此視窗並確認有選取學期與填寫資料</h2></div>`;
                        }
                    }).catch(err => {
                        win.document.body.innerHTML = `<div style='color:red;text-align:center;font-family:sans-serif;margin-top:20vh;'><h2>連線或系統異常，請稍後再試</h2></div>`;
                        console.error(err);
                    });
                ">
                    立即列印 (A4)
                </x-filament::button>
            </div>
        </div>

    </div>
</x-filament-panels::page>