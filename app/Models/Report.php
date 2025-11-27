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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'building_id',
        'created_by_user_id',
        'notifier_id',
        'insurer_id',
        'duplicate_report_id',
    ];

    protected $casts = [
        'damage_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

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

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    /**
     * The CURRENT main status of the report (for fast filtering).
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * The CURRENT optional sub-status of the report (for fast filtering).
     */
    public function subStatus(): BelongsTo
    {
        return $this->belongsTo(SubStatus::class);
    }

    /**
     * The SPECIFIC history record that represents the current state of the report.
     * This gives access to the comment, user, and timestamp of the last change.
     */
    public function currentStatusHistory(): BelongsTo
    {
        return $this->belongsTo(ReportStatusHistory::class, 'current_status_history_id');
    }

    /**
     * The ENTIRE status change history for the report.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class)->latest();
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function closingPayments(): HasMany
    {
        return $this->hasMany(ReportClosingPayment::class);
    }

    public function duplicateOfReport(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_report_id');
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

    public function scopeForBuilding(Builder $query, Building $building): Builder
    {
        return $query->where('building_id', $building->id);
    }
}
