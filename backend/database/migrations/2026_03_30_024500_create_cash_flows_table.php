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
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->enum('category', ['sales', 'purchase', 'initial_balance', 'expense', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->enum('account_type', ['cash', 'bank']);
            $table->string('reference_type')->nullable(); // Invoice, Purchase, etc.
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
        Schema::dropIfExists('cash_flows');
    }
};
