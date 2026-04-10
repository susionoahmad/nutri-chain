<?php
use Illuminate\Contracts\Console\Kernel;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $migs = DB::table('migrations')->pluck('migration')->toArray();
    foreach ($migs as $m) {
        echo $m . "\n";
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}
