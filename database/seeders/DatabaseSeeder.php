<?php

namespace Database\Seeders;

use App\Constants\StreetType;
use App\Constants\ReportStatus;
use App\Constants\ReportSubStatus;
use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    /** This array defines the entire new world structure. */
    private array $newHierarchy = [
        ReportStatus::REPORTED_TO_DAMARISK => [],
        ReportStatus::WAITING_FOR_INSURER_DAMAGE_ID => [],
        ReportStatus::UNDER_INSURER_ADMINISTRATION => [
            ReportSubStatus::ADMIN_INSURER_SETTLEMENT_IN_PROGRESS,
            ReportSubStatus::ADMIN_AWAITING_INSPECTOR_CONTACT,
            ReportSubStatus::ADMIN_AWAITING_INSPECTOR_INSPECTION,
            ReportSubStatus::ADMIN_AWAITING_INSPECTOR_CLOSURE,
            ReportSubStatus::ADMIN_REVIEW_REQUEST_SUBMITTED,
            ReportSubStatus::ADMIN_SUPPLEMENTARY_DOCUMENT_SENT,
            ReportSubStatus::ADMIN_REOPENED,
        ],
        ReportStatus::DATA_OR_DOCUMENT_DEFICIENCY => [
            ReportSubStatus::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT,
            ReportSubStatus::DEFICIENCY_TEMP_CLOSED_INSPECTION,
            ReportSubStatus::DEFICIENCY_TEMP_CLOSED_DOCUMENT,
            ReportSubStatus::DEFICIENCY_DOCUMENT_SENT_TO_DAMARISK,
        ],
        ReportStatus::CLOSED => [
            ReportSubStatus::CLOSED_WITH_PAYMENT,
            ReportSubStatus::CLOSED_WITH_REJECTION,
            ReportSubStatus::CLOSED_CLAIM_WITHDRAWN,
            ReportSubStatus::CLOSED_INCORRECT_REPORT,
            ReportSubStatus::CLOSED_DUPLICATE_REPORT,
            ReportSubStatus::CLOSED_DUE_TO_INDIFFERENCE,
            ReportSubStatus::CLOSED_DELETED,
            ReportSubStatus::CLOSED_ARCHIVED,
        ],
    ];

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
            'uuid' => Str::uuid(),
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
            'street_type' => StreetType::UTCA,
            'street_number' => '1',
            'account_number' => '00000000-00000000-00000001',
            'insurer' => 'Teszt Biztosító Zrt.',
        ]);

        BuildingManagement::create([
            'building_id' => $building->id,
            'customer_id' => $customer->id,
            'start_date' => now(),
        ]);

        $this->seedStatuses();
    }

    private function seedStatuses(): void
    {
        DB::transaction(function () {
            foreach ($this->newHierarchy as $parentName => $subStatusNames) {
                $parentStatus = Status::create(['uuid' => Str::uuid(), 'name' => $parentName]);
                if (!empty($subStatusNames)) {
                    foreach ($subStatusNames as $subName) {
                        SubStatus::create(['uuid' => Str::uuid(), 'status_id' => $parentStatus->id, 'name' => $subName]);
                    }
                }
            }
        });
    }
}
