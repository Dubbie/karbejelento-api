<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAttachment extends Model
{
    use HasFactory;

    protected $table = 'report_attachments';
    public const UPDATED_AT = null; // Disable updated_at

    protected $fillable = [
        'uuid',
        'report_id',
        'uploaded_by_user_id',
        'file_path',
        'file_name_original',
        'file_mime_type',
        'file_size_bytes',
        'category',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
