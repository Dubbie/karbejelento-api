<?php

namespace Database\Factories;

use App\Models\DocumentRequestItem;
use App\Models\DocumentRequestItemFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentRequestItemFile>
 */
class DocumentRequestItemFileFactory extends Factory
{
    protected $model = DocumentRequestItemFile::class;

    public function definition(): array
    {
        $fileName = $this->faker->lexify('doc-?????.pdf');

        return [
            'uuid' => (string) Str::uuid(),
            'document_request_item_id' => DocumentRequestItem::factory(),
            'uploaded_by_user_id' => User::factory(),
            'file_path' => 'document-requests/' . Str::uuid() . '/' . $fileName,
            'file_name_original' => $fileName,
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => $this->faker->numberBetween(1000, 1000000),
            'uploaded_at' => now(),
        ];
    }
}
