<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Link a user with role=customer to a customers record
            $table->foreignId('customer_id')
                  ->nullable()
                  ->after('supplier_id')
                  ->constrained('customers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Customer::class);
            $table->dropColumn('customer_id');
        });
    }
};
