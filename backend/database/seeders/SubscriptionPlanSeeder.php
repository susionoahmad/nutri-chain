<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\SubscriptionPlan::create([
            'name' => 'Trial',
            'price' => 0,
            'duration_days' => 7,
            'max_users' => 3,
            'max_customers' => 3
        ]);

        \App\Models\SubscriptionPlan::create([
            'name' => 'Basic',
            'price' => 150000,
            'duration_days' => 30,
            'max_users' => 10,
            'max_customers' => 20
        ]);

        \App\Models\SubscriptionPlan::create([
            'name' => 'Pro',
            'price' => 500000,
            'duration_days' => 30,
            'max_users' => null,
            'max_customers' => null
        ]);
    }
}
