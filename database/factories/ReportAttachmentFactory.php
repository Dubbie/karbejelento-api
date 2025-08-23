<?php

namespace Database\Factories;

use App\Constants\AttachmentCategory;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportAttachment>
 */
class ReportAttachmentFactory extends Factory
{
    protected $model = ReportAttachment::class;

    public function definition(): array
    {
        $categories = (new \ReflectionClass(AttachmentCategory::class))->getConstants();
        $fileName = $this->faker->word() . '.' . $this->faker->fileExtension();

        return [
            'uuid' => $this->faker->uuid(),
            'report_id' => Report::factory(),
            'uploaded_by_user_id' => User::factory(),
            'file_path' => 'attachments/' . $this->faker->sha256() . '/' . $fileName,
            'file_name_original' => $fileName,
            'file_mime_type' => $this->faker->mimeType(),
            'file_size_bytes' => $this->faker->numberBetween(10000, 5000000),
            'category' => $this->faker->randomElement($categories),
        ];
    }
}
