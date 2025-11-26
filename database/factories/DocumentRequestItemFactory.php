<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentRequestItem>
 */
class DocumentRequestItemFactory extends Factory
{
    protected $model = DocumentRequestItem::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'document_request_id' => DocumentRequest::factory(),
            'label' => $this->faker->sentence(3),
            'note' => $this->faker->optional()->sentence(),
            'position' => $this->faker->numberBetween(1, 5),
        ];
    }
}
