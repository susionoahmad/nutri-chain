<?php
use Illuminate\Contracts\Console\Kernel;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

try {
    echo "Running migrate...\n";
    $status = Artisan::call('migrate', ['--force' => true]);
    echo "Status: " . $status . "\n";
    echo Artisan::output();
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
