<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'event',
        'status_id',
        'sub_status_id',
        'is_active',
        'options',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'options' => 'array',
    ];

    protected $hidden = [
        'id',
        'status_id',
        'sub_status_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $rule) {
            if (empty($rule->uuid)) {
                $rule->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRuleRecipient::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function subStatus(): BelongsTo
    {
        return $this->belongsTo(SubStatus::class);
    }
}
