<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents; // [新增] 事件監聽
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet; // [新增] 處理 Excel 底層
use PhpOffice\PhpSpreadsheet\Cell\DataValidation; // [新增] 資料驗證
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            // 範例資料 1：國小部
            [
                '一年甲班',   // A: 班級
                '01',        // B: 座號
                '112001',    // C: 學號
                '王小明',     // D: 姓名
                '一般生',     // E: 身分 (對應 DTO 預設)
                '在學',       // F: 狀態
                '葷食',       // G: 飲食
            ],
            // 範例資料 2：幼兒園 (對應 DTO 邏輯)
            [
                '太陽班',
                '15',
                'K11205',
                '林小美',
                '低收入戶',   // 對應 DTO: str_contains('低收')
                '在學',
                '全素',       // 對應 DTO: str_contains('全素')
            ],
        ];
    }

    public function headings(): array
    {
        return [
            '班級 (必填)',
            '座號 (必填)',
            '學號 (必填)',
            '姓名 (必填)',
            '身分 (選填)',
            '狀態 (選填)',
            '飲食 (選填)',
        ];
    }

    public function title(): string
    {
        return '學生匯入範本';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // 1. 標題列：淺灰底、粗體、置中、四邊框
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // 2. 必填欄位 (A-D) 標題紅字
            'A1:D1' => ['font' => ['color' => ['rgb' => 'D32F2F'], 'bold' => true]],
        ];
    }

    /**
     * [宗師奧義] 註冊事件：植入下拉選單與資料驗證
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $rowCount = 1000; // 設定驗證範圍到 1000 列 (夠用了)

                // --- 設定選項清單 (必須與 DTO 邏輯匹配) ---
                $identityOptions = '"一般生,低收入戶,中低收入戶,身心障礙,原住民,家庭突發因素"';
                $statusOptions = '"在學,轉出,休學,畢業"';
                $dietOptions = '"葷食,全素,蛋奶素"';

                // --- E 欄：身分 (Identity) 下拉選單 ---
                $this->addDropdown($sheet, "E2:E{$rowCount}", $identityOptions);

                // --- F 欄：狀態 (Status) 下拉選單 ---
                $this->addDropdown($sheet, "F2:F{$rowCount}", $statusOptions);

                // --- G 欄：飲食 (Diet) 下拉選單 ---
                $this->addDropdown($sheet, "G2:G{$rowCount}", $dietOptions);

                // --- 加分題：設定欄位寬度 ---
                $sheet->getColumnDimension('A')->setWidth(15); // 班級
                $sheet->getColumnDimension('C')->setWidth(15); // 學號
                $sheet->getColumnDimension('D')->setWidth(15); // 姓名
            },
        ];
    }

    /**
     * 封裝下拉選單邏輯
     */
    protected function addDropdown($sheet, string $cellRange, string $options)
    {
        $validation = $sheet->getCell(explode(':', $cellRange)[0])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('輸入錯誤');
        $validation->setError('請從清單中選擇有效的選項。');
        $validation->setFormula1($options);

        // 將驗證規則複製到整欄
        $sheet->setDataValidation($cellRange, $validation);
    }
}
