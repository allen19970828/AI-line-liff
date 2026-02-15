<?php

namespace App\Imports;

use App\Services\StaffService;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // [建議] 加這個自動跳過空行

class StaffImport implements SkipsEmptyRows, ToModel, WithHeadingRow
{
    // ... (unchanged)

    public function __construct(
        protected int $schoolId
    ) {}

    public function model(array $row)
    {
        // 直接從容器中解析 Service (Lazy Loading)
        $service = app(StaffService::class);

        // 交給軍師處理
        return $service->upsertStaffFromImport($row, $this->schoolId);
    }
}
