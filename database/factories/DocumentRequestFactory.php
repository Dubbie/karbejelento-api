<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentRequest>
 */
class DocumentRequestFactory extends Factory
{
    protected $model = DocumentRequest::class;

    public function definition(): array
    {
        $documents = ['ID Card', 'Proof of ownership'];

        return [
            'uuid' => (string) Str::uuid(),
            'report_id' => Report::factory(),
            'requested_by_user_id' => User::factory(),
            'email_title' => $this->faker->sentence(),
            'email_body' => $this->faker->paragraph(),
            'requested_documents' => $documents,
            'other_document_note' => $this->faker->optional()->sentence(),
            'is_fulfilled' => false,
            'public_token' => Str::random(40),
            'sent_at' => now(),
        ];
    }
}
