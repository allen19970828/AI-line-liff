<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            同步國定假日
        </x-slot>

        <x-slot name="description">
            從政府開放資料平台同步指定年份的國定假日資料。
        </x-slot>

        <form wire:submit="sync" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit">
                    開始同步
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
