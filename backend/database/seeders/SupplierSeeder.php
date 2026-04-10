<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplier = \App\Models\Supplier::create(['name' => 'Nutri SPPG Utama']);
        
        \App\Models\User::create([
            'name' => 'Owner Nutri',
            'email' => 'owner@nutri.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'supplier_id' => $supplier->id,
            'role' => 'owner'
        ]);

        \App\Models\Customer::create([
            'supplier_id' => $supplier->id,
            'name' => 'SPPG Kitchen A',
            'address' => 'Jakarta Selatan',
            'phone' => '08123456789'
        ]);
    }

}
