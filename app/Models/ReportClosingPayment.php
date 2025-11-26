<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportClosingPayment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'payment_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
