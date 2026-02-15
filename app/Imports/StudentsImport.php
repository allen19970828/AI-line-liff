<?php

namespace App\Imports;

use App\DTOs\StudentImportData;
use App\Services\StudentService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow; // [宗師推薦] 改用這個
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, SkipsEmptyRows, WithHeadingRow
{
    public function __construct(
        protected int $schoolId
    ) {}

    /**
     * [宗師天眼] 指定標題列位置
     * - 若 Excel 第 1 列直接是「班級、姓名、學號...」，則 return 1
     * - 若第 1 列是「學生匯入範本」等大標題，第 2 列才是欄位名，則 return 2
     */
    public function headingRow(): int
    {
        return 1; // 請依實際 Excel 範本調整
    }

    /**
     * 每一行資料都會流進這裡
     */
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowArray = $row->toArray();

        // [宗師天眼] 印出每一行的原始資料與 Key
        // 若中文被轉成拼音或空字串，這裡會一目了然
        Log::info("Excel Row {$rowIndex} 原始資料:", $rowArray);

        // 1. 快速防呆 (標題列重複出現的情況)
        if (($rowArray['姓名'] ?? '') === '姓名') {
            return;
        }

        try {
            // 2. 呼叫 DTO 清洗資料
            // 這裡會把中文轉成 Enum，並檢查必填
            $dto = StudentImportData::fromRow($rowArray);

            // 3. 呼叫 Service 執行業務邏輯 (找班級ID -> updateOrCreate)
            // 我們把 DTO 傳進去，讓 Service 保持乾淨
            app(StudentService::class)->importStudent($dto, $this->schoolId);

        } catch (\Exception $e) {
            // 記錄錯誤但繼續跑下一行
            Log::error("學生匯入失敗 (Row {$rowIndex}): ".$e->getMessage(), [
                'row' => $rowArray,
            ]);
        }
    }
}
