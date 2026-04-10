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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment'])->comment('in: purchase/return, out: sales, adjustment: manual');
            $table->decimal('qty', 15, 2);
            $table->decimal('old_qty', 15, 2);
            $table->decimal('new_qty', 15, 2);
            $table->string('reference_type')->nullable()->comment('Purchase, Order, etc');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
