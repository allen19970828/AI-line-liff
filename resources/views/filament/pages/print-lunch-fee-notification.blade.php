<x-filament-panels::page>
    <div class="flex flex-col gap-6" x-data
        @open-print-window.window="window.open($event.detail.url, '_blank').focus()">

        <!-- 控制面板 (僅螢幕顯示) -->
        <x-filament::section heading="通知單內容設定">
            {{ $this->form }}

            <x-slot name="headerEnd">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="$refresh">
                    設定重設與儲存
                </x-filament::button>
            </x-slot>

            <div class="mt-6 flex justify-end gap-3 w-full">
                {{-- [重構] 不再使用 $wire 異步調用，改用標準 HTML Form POST 確保在高負載/複雜狀態下的穩定性 --}}
                <form action="{{ route('admin.print.lunch-fee-notification.render') }}" method="POST" target="_blank"
                    x-ref="printForm">
                    @csrf
                    <input type="hidden" name="data" :value="JSON.stringify($wire.data)">

                    <x-filament::button type="submit" icon="heroicon-o-printer" size="lg"
                        x-on:click="if(!$wire.data.semester_id) { $event.preventDefault(); new FilamentNotification().title('請先選擇學期').warning().send(); }">
                        立即列印 (A4)
                    </x-filament::button>
                </form>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
