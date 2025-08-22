<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Damage Solver User',
            'email' => 'damagesolver@example.com',
            'role' => 'damage_solver',
        ]);

        User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);

        User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);
    }
}
