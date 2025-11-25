<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'requested_documents' => 'array',
        'is_fulfilled' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentRequestItem::class)->orderBy('position');
    }

    public function documentRequestItems(): HasMany
    {
        return $this->items();
    }
}
