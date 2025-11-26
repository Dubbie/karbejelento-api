<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRequestItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentRequestItemFile::class);
    }
}
