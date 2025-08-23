<?php

namespace App\Models;

use App\Constants\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

    // Use guarded instead of fillable when you have many fields
    protected $guarded = ['id'];

    protected $casts = [
        'damage_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- Relationships ---

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function notifier(): BelongsTo
    {
        return $this->belongsTo(Notifier::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        // Admins and Damage Solvers see everything
        if (in_array($user->role, [UserRole::ADMIN, UserRole::DAMAGE_SOLVER])) {
            return $query;
        }

        // A report is visible if its associated building is visible.
        return $query->whereHas('building', function ($buildingQuery) use ($user) {
            $buildingQuery->forUser($user);
        });
    }
}
