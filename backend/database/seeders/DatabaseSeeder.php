<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // SAAS SUPERADMIN: Akun Penguasa / Pemilik Utama Platform Nutri-Chain
        User::create([
            'name' => 'Nutri-Chain Super Administrator',
            'email' => 'superadmin@nutrichain.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'superadmin',
            'supplier_id' => null, // Superadmin tidak terikat pada satupun supplier
            'customer_id' => null,
            'email_verified_at' => now(),
        ]);
        
        $this->command->info('Superadmin berhasil dibuat! (Email: superadmin@nutrichain.com | Password: password)');
    }
}
