<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('qty')->default(0);
            $table->timestamps();
        });

        // Migrate existing data if any exists
        $products = \Illuminate\Support\Facades\DB::table('products')->get();
        foreach ($products as $product) {
            if (isset($product->stock)) {
                \Illuminate\Support\Facades\DB::table('stocks')->insert([
                    'product_id' => $product->id,
                    'qty' => $product->stock,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop stock from products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('price');
        });

        // Try to recover data back
        $stocks = \Illuminate\Support\Facades\DB::table('stocks')->get();
        foreach ($stocks as $stock) {
            \Illuminate\Support\Facades\DB::table('products')
                ->where('id', $stock->product_id)
                ->update(['stock' => $stock->qty]);
        }

        Schema::dropIfExists('stocks');
    }
};
