<?php

use App\Http\Controllers\Api\Liff\MealCancellationController;
use App\Models\User;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::where('name', '俞良澄')->first();

if (! $user) {
    echo "User not found!\n";
    exit;
}

echo "Testing User: {$user->name} (Role: ".($user->staff?->staff_role->value ?? 'N/A').")\n";

// Mock Request
$request = Request::create('/api/cancellation/targets', 'GET');
$request->setUserResolver(fn () => $user);

// Create Controller Instance
$controller = app(MealCancellationController::class);

// Execute
try {
    $response = $controller->getTargets($request);

    $data = $response->getData(true);

    echo 'Status Code: '.$response->getStatusCode()."\n";
    echo 'Targets Found: '.count($data['data'])."\n";

    if (count($data['data']) > 0) {
        echo 'First Target: '.json_encode($data['data'][0], JSON_UNESCAPED_UNICODE)."\n";
        echo 'Last Target: '.json_encode(end($data['data']), JSON_UNESCAPED_UNICODE)."\n";
    }

} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo $e->getTraceAsString();
}
