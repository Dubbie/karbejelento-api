<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->uuid)) {
                $user->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the manager that this user (customer) belongs to.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * Get the customers that this user (manager) has.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * Get the notifiers associated with this user (customer).
     */
    public function notifiers(): HasMany
    {
        return $this->hasMany(Notifier::class, 'customer_id');
    }

    /**
     * The management records associated with this user (customer).
     */
    public function managementHistory(): HasMany
    {
        return $this->hasMany(BuildingManagement::class, 'customer_id');
    }

    /**
     * Get all of the buildings that this user (customer) manages.
     */
    public function buildings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Building::class,
            BuildingManagement::class,
            'customer_id',      // The key on the intermediate table for this model.
            'id',               // The key on the final table.
            'id',               // The key on this model.
            'building_id'       // The key on the intermediate table for the final model.
        );
    }
}
