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
        Schema::create('saas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default values
        DB::table('saas_settings')->insert([
            ['key' => 'bank_name', 'value' => 'Bank BCA', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'bank_account_number', 'value' => '123-456-7890', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'bank_account_name', 'value' => 'PT Nutri Chain Solusi', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_email', 'value' => 'support@nutrichain.com', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_whatsapp', 'value' => '6281234567890', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_settings');
    }
};
