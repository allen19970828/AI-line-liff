<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    // 宗師診斷：如果這裡回傳 []，下載下來就會是一張白紙。
    public function array(): array
    {
        return [
            // 範例資料：讓使用者知道怎麼填
            [
                '王大明',   // 姓名
                '',         // 部別
                '教師',     // 身分
                '葷',       // 飲食
                '',         // 用餐
                '',         // 用餐日
                '',         // 編號
            ],
            [
                '陳阿姨',
                '國小部',
                '廚工',     // 測試 Worker
                '素',
                '是',
                '1,3,5',    // 測試兼職
                '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            '姓名',      // A欄
            '部別',      // B欄
            '身分',      // C欄
            '飲食',      // D欄
            '用餐',      // E欄
            '用餐日',    // F欄
            '編號',      // G欄
        ];
    }

    public function title(): string
    {
        return '教職員匯入範本';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // 標題粗體
            'A' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']]], // 姓名紅字
        ];
    }
}
