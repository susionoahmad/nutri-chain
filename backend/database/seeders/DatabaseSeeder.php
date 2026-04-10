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
        // 1. Buat Data Supplier Utama (Perusahaan / Vendor)
        $supplier = \App\Models\Supplier::create([
            'name' => 'Nutri-Chain Main Branch',
            'code' => 'NUTRICHAINHQ', // Contoh kode unik
            'email' => 'contact@nutrichain.com',
            'phone' => '+6280000000',
            'address' => 'Jakarta HQ',
        ]);

        // 2. Buat Data Profil Customer SPPG Dummy
        $customerProfile = \App\Models\Customer::create([
            'supplier_id' => $supplier->id,
            'name' => 'Contoh SPPG Plesungan',
            'address' => 'Jl. Plesungan No 123',
            'phone' => '+628111222333',
        ]);

        $roles = ['owner', 'admin', 'warehouse', 'driver', 'customer'];

        foreach ($roles as $role) {
            $userData = [
                'name' => ucfirst($role) . ' User',
                'email' => $role . '@nutrichain.com',
                'role' => $role,
                'supplier_id' => $supplier->id,
            ];

            // Khusus untuk role customer, tautkan ke profil customer_id yang sudah dibuat
            if ($role === 'customer') {
                $userData['customer_id'] = $customerProfile->id;
            }

            User::factory()->create($userData);
        }
    }
}
