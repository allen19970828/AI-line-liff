<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $semester = App\Models\Semester::first();
    if (storage/logs/laravel.logsemester) die("No semester found.");
    
    $start = $semester->start_date;
    $p1End = $start->copy()->month(11)->endOfMonth();
    echo "P1End: " . $p1End->toDateString() . "\n";
    
    $service = app(App\Services\MealBillingService::class);
    $matrix = $service->generateCalendarMatrix($semester);
    echo "Matrix generated.\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
