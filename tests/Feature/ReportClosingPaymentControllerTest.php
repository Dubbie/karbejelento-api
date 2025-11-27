<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ReportClosingPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportClosingPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_list_closing_payments(): void
    {
        $manager = User::factory()->manager()->create();
        $report = Report::factory()->create();
        ReportClosingPayment::factory()->count(2)->create(['report_id' => $report->id]);

        Sanctum::actingAs($manager);

        $response = $this->getJson('/api/v1/reports/' . $report->uuid . '/closing-payments');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_manager_can_create_closing_payment(): void
    {
        $manager = User::factory()->manager()->create();
        $report = Report::factory()->create();

        Sanctum::actingAs($manager);

        $payload = [
            'recipient' => 'Test Recipient',
            'amount' => 1000.50,
            'currency' => 'huf',
            'payment_date' => '2025-12-01',
            'payment_time' => '09:45',
        ];

        $response = $this->postJson('/api/v1/reports/' . $report->uuid . '/closing-payments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('recipient', 'Test Recipient')
            ->assertJsonPath('currency', 'HUF');

        $this->assertDatabaseHas('report_closing_payments', [
            'report_id' => $report->id,
            'recipient' => 'Test Recipient',
            'amount' => 1000.50,
            'currency' => 'HUF',
            'payment_time' => '09:45',
        ]);
    }

    public function test_manager_can_update_closing_payment(): void
    {
        $manager = User::factory()->manager()->create();
        $report = Report::factory()->create();
        $payment = ReportClosingPayment::factory()->create([
            'report_id' => $report->id,
            'currency' => 'HUF',
        ]);

        Sanctum::actingAs($manager);

        $response = $this->patchJson(
            '/api/v1/reports/' . $report->uuid . '/closing-payments/' . $payment->uuid,
            ['amount' => 2222.00, 'currency' => 'eur']
        );

        $response->assertOk()
            ->assertJsonPath('amount', 2222)
            ->assertJsonPath('currency', 'EUR');

        $this->assertDatabaseHas('report_closing_payments', [
            'id' => $payment->id,
            'amount' => 2222,
            'currency' => 'EUR',
        ]);
    }

    public function test_manager_can_delete_closing_payment(): void
    {
        $manager = User::factory()->manager()->create();
        $report = Report::factory()->create();
        $payment = ReportClosingPayment::factory()->create(['report_id' => $report->id]);

        Sanctum::actingAs($manager);

        $response = $this->deleteJson(
            '/api/v1/reports/' . $report->uuid . '/closing-payments/' . $payment->uuid
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('report_closing_payments', [
            'id' => $payment->id,
        ]);
    }
}
