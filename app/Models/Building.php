<?php

namespace App\Models;

use App\Constants\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Str;

/**
 * @mixin \App\Traits\Paginatable
 */
class Building extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'buildings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'postcode',
        'city',
        'street_name',
        'street_type',
        'street_number',
        'bond_number',
        'account_number',
        'insurer',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     * This makes 'formatted_address' and 'current_customer'
     * automatically appear when you convert the model to JSON.
     *
     * @var array
     */
    protected $appends = [
        'formatted_address',
        'current_customer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the management history for the building.
     */
    public function managementHistory(): HasMany
    {
        // We order by start_date descending so the most recent is first.
        return $this->hasMany(BuildingManagement::class)->orderBy('start_date', 'desc');
    }

    /**
     * Get the building's full, formatted address.
     */
    protected function getFormattedAddressAttribute(): string
    {
        return "{$this->postcode} {$this->city}, {$this->street_name} {$this->street_type} {$this->street_number}";
    }

    /**
     * Get the current customer managing the building.
     */
    protected function getCurrentCustomerAttribute(): ?User
    {
        // Find the first management record where end_date is null.
        // Because we ordered the relationship by date, the first one is the current one.
        $currentManagement = $this->managementHistory->firstWhere('end_date', null);

        // If a record is found, return its associated customer. Otherwise, return null.
        return $currentManagement?->customer;
    }

    /**
     * Scopes the query to only include buildings the user is allowed to see.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        // Admins and Damage Solvers can see all buildings
        if (in_array($user->role, [UserRole::ADMIN, UserRole::DAMAGE_SOLVER])) {
            return $query;
        }

        // Managers can see all buildings managed by their customers
        if ($user->role === UserRole::MANAGER) {
            $customerIds = $user->customers()->pluck('id');
            return $query->whereHas('managementHistory', function ($q) use ($customerIds) {
                $q->whereIn('customer_id', $customerIds);
            });
        }

        // Customers can only see buildings they are directly managing
        if ($user->role === UserRole::CUSTOMER) {
            return $query->whereHas('managementHistory', function ($q) use ($user) {
                $q->where('customer_id', $user->id);
            });
        }

        // By default, return no buildings if role is unrecognized
        return $query->whereRaw('1 = 0');
    }
}
