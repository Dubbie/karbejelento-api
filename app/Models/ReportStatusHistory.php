<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'report_status_history';
    public const UPDATED_AT = null; // Disable updated_at

    protected $fillable = [
        'report_id',
        'user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
