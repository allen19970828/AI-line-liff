<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        <!-- 控制面板 (僅螢幕顯示) -->
        <div
            class="no-print bg-white p-6 rounded-xl shadow-sm border border-gray-100 dark:bg-gray-900 dark:border-gray-800">
            <div class="mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">
                <h3 class="text-lg font-bold">通知單內容設定</h3>
            </div>
            {{ $this->form }}
            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="$refresh">
                    重新計算
                </x-filament::button>
                <x-filament::button icon="heroicon-o-printer" onclick="window.print()" size="lg">
                    立即列印 (A4)
                </x-filament::button>
            </div>
        </div>

        <!-- 列印預覽區 (模擬 A4 紙張) -->
        <div class="print-container bg-white text-black mx-auto overflow-hidden"
            style="width: 210mm; min-height: 297mm; padding: 15mm; font-family: 'PingFang TC', 'Microsoft JhengHei', sans-serif; line-height: 1.5;">

            <!-- 標題 -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold tracking-widest border-b-2 border-black inline-block pb-1 px-4">
                    {{ $data['school_name'] }}{{ $data['academic_year'] }}學年度第{{ $data['term'] }}學期午餐費通知單
                </h1>
            </div>

            <!-- 致家長文字 -->
            <div class="mb-4 text-[1.1rem]">
                <p class="font-bold underline underline-offset-4 decoration-1">{{ $data['header'] }}</p>
                <p class="mt-3 leading-relaxed text-justify">{{ $data['intro'] }}</p>
            </div>

            <div class="text-right mb-6 font-bold text-xl">
                {{ $data['school_name'] }}&nbsp;&nbsp;午餐秘書敬上
            </div>

            <!-- 日曆區域 -->
            <div class="calendar-section mb-6">
                <!-- 星期標題 -->
                <div class="grid grid-cols-7 border-t border-l border-black bg-gray-50">
                    @foreach(['日', '一', '二', '三', '四', '五', '六'] as $day)
                        <div class="border-r border-b border-black p-1 text-center font-bold text-lg">{{ $day }}</div>
                    @endforeach
                </div>

                <!-- 日曆格 -->
                <div class="grid grid-cols-7 border-l border-black">
                    @php
                        $calendar = $this->getCalendarData();
                    @endphp

                    @foreach($calendar as $month)
                        @foreach($month['weeks'] as $week)
                            @foreach($week as $day)
                                <div @class([
                                    'border-r border-b border-black text-center min-h-[2.2rem] flex flex-col justify-center items-center relative',
                                    'bg-orange-200 print:!bg-orange-200' => !$day['is_meal_day'] && $day['is_current_month'],
                                    'bg-gray-100 dark:bg-gray-100 text-transparent' => !$day['is_current_month'],
                                ])>
                                    @if($day['is_current_month'])
                                        <span class="text-lg font-medium">{{ $day['day'] }}</span>
                                        @if($day['day'] == 1)
                                            <span
                                                class="absolute top-0 right-0.5 text-[10px] font-bold text-gray-600">{{ $day['date']->format('n/') }}</span>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
                    @endforeach
                </div>
            </div>

            <!-- 費用統計表格 -->
            <div class="billing-section mb-8">
                <h2 class="font-bold text-xl mb-3">[午餐費計算]</h2>
                @php
                    $overview = $this->getBillingOverview();
                @endphp
                <table class="w-full border-collapse border-2 border-black text-center text-[1rem]">
                    <thead>
                        <tr class="bg-gray-50 font-bold">
                            <th class="border-2 border-black p-2 w-[14%]"></th>
                            <th class="border-2 border-black p-2 text-red-600 w-[21%]">全部用餐<br>9-11月天數/費用<br>12-1月天數/費用
                            </th>
                            <th class="border-2 border-black p-2 text-red-600 w-[21%]">只吃週二<br>9-11月天數/費用<br>12-1月天數/費用
                            </th>
                            <th class="border-2 border-black p-2 text-red-600 w-[21%]">週三不用餐<br>9-11月天數/費用<br>12-1月天數/費用
                            </th>
                            <th class="border-2 border-black p-2 text-red-600 w-[21%]">
                                三、五不用餐<br>9-11月天數/費用<br>12-1月天數/費用</th>
                            <th class="border-2 border-black p-2 w-[10%]">備註</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overview['rows'] as $idx => $row)
                            <tr>
                                <td class="border-2 border-black p-3 font-bold text-lg leading-tight">{{ $row['label'] }}
                                </td>
                                @foreach(['all', 'tue_only', 'no_wed', 'no_wed_fri'] as $pKey)
                                    <td class="border-2 border-black p-3 whitespace-nowrap">
                                        {{ $row['patterns'][$pKey]['p1_days'] }}天/{{ number_format($row['patterns'][$pKey]['p1_fee']) }}元<br>
                                        {{ $row['patterns'][$pKey]['p2_days'] }}天/{{ number_format($row['patterns'][$pKey]['p2_fee']) }}元
                                    </td>
                                @endforeach
                                <td class="border-2 border-black p-3">
                                    @if($idx == 3) <span class="text-sm">12/4<br>(四)</span> @elseif($idx == 5) <span
                                    class="text-sm">12/4.5<br>(四五)</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 頁尾備註 -->
            <div class="footer-section text-lg space-y-3 whitespace-pre-line font-bold leading-relaxed">
                <hr class="border-black border-px mb-4">
                {{ $data['footer'] }}
            </div>
        </div>
    </div>

    <style>
        /* 避免深色模式影響列印預覽 */
        .print-container {
            color: black !important;
            background-color: white !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .fi-main-ctn,
            .fi-main,
            .fi-body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .fi-topbar,
            .fi-sidebar {
                display: none !important;
            }

            .print-container {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                padding: 0 !important;
            }

            @page {
                size: A4;
                margin: 10mm 15mm;
            }

            /* 強制背景顏色列印 */
            .bg-orange-200 {
                background-color: #fed7aa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</x-filament-panels::page>