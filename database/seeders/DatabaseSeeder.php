<?php

namespace Database\Seeders;

use App\Constants\StreetType;
use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


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

        $manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);

        $customer = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'manager_id' => $manager->id,
        ]);

        $customer->notifiers()->create([
            'name' => 'Kovács János ',
            'email' => 'kovacs.janos@example.com',
            'phone_number' => '06301234567',
        ]);

        $building = Building::create([
            'uuid' => Str::uuid(),
            'name' => 'TH Teszt Ház',
            'bond_number' => '123456789',
            'city' => 'Budapest',
            'postcode' => '1234',
            'street_name' => 'Fő',
            'street_type' => StreetType::STREET,
            'street_number' => '1',
            'account_number' => '00000000-00000000-00000001',
            'insurer' => 'Teszt Biztosító Zrt.',
        ]);

        BuildingManagement::create([
            'building_id' => $building->id,
            'customer_id' => $customer->id,
            'start_date' => now(),
        ]);
    }
}
