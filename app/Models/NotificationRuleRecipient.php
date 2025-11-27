<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRuleRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_rule_id',
        'recipient_type',
        'recipient_value',
    ];

    protected $hidden = [
        'id',
        'notification_rule_id',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'notification_rule_id');
    }
}
