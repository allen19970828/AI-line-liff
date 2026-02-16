<?php

use App\Enums\Meals\CancelLeaveType;
use Illuminate\Support\Facades\Validator;

require __DIR__.'/bootstrap/app.php';

// Simulate request data
$data = [
    'target_id' => 1, // Change to a valid ID
    'type' => 'student',
    'date' => '2026-02-15', // Past date?
    'leave_type' => 'personal',
    'reason' => 'Test reason',
];

$validator = Validator::make($data, [
    'target_id' => 'required', // ID
    'type' => 'required|in:student,staff', // 類型
    'date' => 'required|date|after_or_equal:today',
    'leave_type' => 'nullable|string',
    'reason' => 'nullable|string|max:200',
]);

if ($validator->fails()) {
    echo "Validation Failed:\n";
    print_r($validator->errors()->toArray());
} else {
    echo "Validation Passed!\n";
}

// Check Enum
echo "\nEnum Cases:\n";
foreach (CancelLeaveType::cases() as $case) {
    echo $case->value."\n";
}
