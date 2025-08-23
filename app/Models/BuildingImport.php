<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingImport extends Model
{
    /** @use HasFactory<\Database\Factories\BuildingImportFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'customer_id',
        'status',
        'original_filename',
        'stored_path',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'errors',
    ];

    /**
     * Define the column that should be used for implicit route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'errors' => 'json',
        ];
    }

    /**
     * The user (admin/manager) who initiated the import.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The customer for whom the buildings are being imported.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
