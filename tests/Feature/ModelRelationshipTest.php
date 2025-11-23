<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingManagement;
use App\Models\Notifier;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationships(): void
    {
        /** @var User $manager */
        $manager = User::factory()->manager()->create();
        /** @var User $customer */
        $customer = User::factory()->customer()->create(['manager_id' => $manager->id]);

        Notifier::factory()->create(['customer_id' => $customer->id]);

        $building = Building::factory()->create();
        BuildingManagement::factory()->create([
            'customer_id' => $customer->id,
            'building_id' => $building->id,
            'insurer_id' => $building->insurer_id,
        ]);

        // Assert the relationships exist and return the correct type
        $this->assertTrue($customer->manager->is($manager));
        $this->assertTrue($manager->customers->contains($customer));
        $this->assertInstanceOf(Notifier::class, $customer->notifiers->first());
        $this->assertInstanceOf(Building::class, $customer->buildings->first());
    }

    public function test_building_management_relationships(): void
    {
        /** @var User $customer */
        $customer = User::factory()->customer()->create();
        $building = Building::factory()->create();
        $management = BuildingManagement::factory()->create([
            'customer_id' => $customer->id,
            'building_id' => $building->id,
            'insurer_id' => $building->insurer_id,
        ]);

        $this->assertTrue($management->customer->is($customer));
        $this->assertTrue($management->building->is($building));
    }

    public function test_report_attachment_relationships(): void
    {
        /** @var User $uploader */
        $uploader = User::factory()->create();
        $report = Report::factory()->create();
        $attachment = ReportAttachment::factory()->create([
            'report_id' => $report->id,
            'uploaded_by_user_id' => $uploader->id,
        ]);

        $this->assertTrue($attachment->report->is($report));
        $this->assertTrue($attachment->uploadedBy->is($uploader));
    }
}
