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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('id')->constrained('suppliers')->onDelete('cascade');
        });

        // Backfill data
        DB::table('invoices')->join('orders', 'invoices.order_id', '=', 'orders.id')
            ->update(['invoices.supplier_id' => DB::raw('orders.supplier_id')]);

        // Make it non-nullable after backfill
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
