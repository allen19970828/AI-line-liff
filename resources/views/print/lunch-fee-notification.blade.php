<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['school_name'] }} - 午餐費通知單列印</title>
    @vite(['resources/css/app.css'])
    <style>
        /* A4 紙張列印設定 */
        @page {
            size: A4;
            margin: 10mm 15mm;
        }

        body {
            background-color: white;
            color: black;
            font-family: 'PingFang TC', 'Microsoft JhengHei', sans-serif;
            line-height: 1.5;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 強制背景顏色列印 */
        .bg-orange-200 {
            background-color: #fed7aa !important;
        }

        .bg-gray-50 {
            background-color: #f9fafb !important;
        }

        .bg-gray-100 {
            background-color: #f3f4f6 !important;
        }

        .print-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
        }

        @media screen {
            body {
                background-color: #f3f4f6;
                padding-top: 2rem;
                padding-bottom: 2rem;
            }

            .print-container {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .print-tips {
                text-align: center;
                margin-bottom: 1rem;
                color: #4b5563;
                font-size: 0.875rem;
            }
        }

        @media print {
            .print-tips {
                display: none !important;
            }

            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }

            .print-container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="print-tips">
        準備列印中... 如果列印視窗沒有自動彈出，請按下 Ctrl+P (Windows) 或 Cmd+P (Mac)。
    </div>

    <!-- 列印預覽區 (模擬 A4 紙張) -->
    <div class="print-container bg-white text-black mx-auto overflow-hidden">
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
                @foreach($calendar as $month)
                    @foreach($month['weeks'] as $week)
                        @foreach($week as $day)
                            <div
                                class="border-r border-b border-black text-center min-h-[2.2rem] flex flex-col justify-center items-center relative {{ !$day['is_meal_day'] && $day['is_current_month'] ? 'bg-orange-200' : '' }} {{ !$day['is_current_month'] ? 'bg-gray-100 text-transparent' : '' }}">
                                @if($day['is_current_month'])
                                    <span class="text-lg font-medium">{{ $day['day'] }}</span>
                                    @if($day['day'] == 1)
                                        <span
                                            class="absolute top-0 right-0.5 text-[10px] font-bold text-gray-600">{{ \Carbon\Carbon::parse($day['date'])->format('n/') }}</span>
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
            <table class="w-full border-collapse border-2 border-black text-center text-[1rem]">
                <thead>
                    <tr class="bg-gray-50 font-bold">
                        <th class="border-2 border-black p-2 w-[14%]"></th>
                        <th class="border-2 border-black p-2 text-red-600 w-[21%]">全部用餐<br>9-11月天數/費用<br>12-1月天數/費用</th>
                        <th class="border-2 border-black p-2 text-red-600 w-[21%]">只吃週二<br>9-11月天數/費用<br>12-1月天數/費用</th>
                        <th class="border-2 border-black p-2 text-red-600 w-[21%]">週三不用餐<br>9-11月天數/費用<br>12-1月天數/費用
                        </th>
                        <th class="border-2 border-black p-2 text-red-600 w-[21%]">三、五不用餐<br>9-11月天數/費用<br>12-1月天數/費用
                        </th>
                        <th class="border-2 border-black p-2 w-[10%]">備註</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overview['rows'] ?? [] as $idx => $row)
                        <tr>
                            <td class="border-2 border-black p-3 font-bold text-lg leading-tight">{{ $row['label'] }}</td>
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

    <script>
        // 頁面載入後自動觸發列印
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500); // 稍微延遲讓樣式確實套用
        };
    </script>
</body>

</html>