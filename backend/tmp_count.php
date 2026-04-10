<?php
require 'd:/PROJECT/nutri-chain/backend/vendor/autoload.php';
$app = require_once 'd:/PROJECT/nutri-chain/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$mutations = \App\Models\StockMutation::count();
echo "StockMutation Count: " . $mutations . "\n";
$m = \App\Models\StockMutation::first();
echo "First Mutation: \n";
print_r($m ? $m->toArray() : []);
