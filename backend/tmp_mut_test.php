<?php
require 'd:/PROJECT/nutri-chain/backend/vendor/autoload.php';
$app = require_once 'd:/PROJECT/nutri-chain/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$supplier_id = 2; // Vinessa

$query = \App\Models\StockMutation::with('product')
            ->where('supplier_id', $supplier_id);
            
$mutations = $query->latest()->paginate(50);
echo json_encode($mutations);
