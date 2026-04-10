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
        Schema::table('products', function (Blueprint $table) {
            $table->index('supplier_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('supplier_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('supplier_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['supplier_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['supplier_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['status']);
        });
    }
};
