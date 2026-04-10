<?php
use Illuminate\Contracts\Console\Kernel;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "Dropping subscriptions...\n";
    Schema::dropIfExists('subscriptions');
    echo "Dropping subscription_plans...\n";
    Schema::dropIfExists('subscription_plans');
    
    echo "Checking if columns exist in suppliers...\n";
    if (Schema::hasColumn('suppliers', 'is_active')) {
        echo "Dropping is_active from suppliers...\n";
        Schema::table('suppliers', function ($table) {
            $table->dropColumn(['is_active', 'valid_until']);
        });
    }
    
    echo "Deleting from migrations table...\n";
    DB::table('migrations')->where('migration', 'like', '%subscription%')->delete();
    DB::table('migrations')->where('migration', 'like', '%saas%')->delete();
    
    echo "Cleanup DONE.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
