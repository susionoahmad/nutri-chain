<?php
require 'd:/PROJECT/nutri-chain/backend/vendor/autoload.php';
$app = require_once 'd:/PROJECT/nutri-chain/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all();
echo "Users:\n";
foreach($users as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, Role: {$u->role}, SupplierID: {$u->supplier_id}\n";
}

$purchases = \App\Models\Purchase::all();
echo "\nPurchases:\n";
foreach($purchases as $p) {
    echo "ID: {$p->id}, SuppID: {$p->supplier_id}, Total: {$p->total_amount}, Status: {$p->status}\n";
}

$mutations = \App\Models\StockMutation::all();
echo "\nStockMutations:\n";
foreach($mutations as $m) {
    echo "ID: {$m->id}, SuppID: {$m->supplier_id}, ProdID: {$m->product_id}, Type: {$m->type}\n";
}
