<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$supplierIdsWithProducts = \App\Models\Product::pluck('supplier_id')->unique()->toArray();
echo "Updated (has products): " . \App\Models\Supplier::whereIn('id', $supplierIdsWithProducts)->update(['is_onboarded' => 1]) . "\n";

$supplierIdsWithUsers = \App\Models\User::where('role', '!=', 'owner')->where('role', '!=', 'superadmin')->where('role', '!=', 'customer')->pluck('supplier_id')->filter()->unique()->toArray();
echo "Updated (has team users): " . \App\Models\Supplier::whereIn('id', $supplierIdsWithUsers)->update(['is_onboarded' => 1]) . "\n";

$allUnonboarded = \App\Models\Supplier::where('is_onboarded', 0)->get();
echo "Remaining Unonboarded Suppliers:\n";
foreach($allUnonboarded as $supplier) {
    echo "- " . $supplier->name . "\n";
}
