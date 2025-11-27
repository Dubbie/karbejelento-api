<?php

namespace App\Constants;

final class NotificationEvent
{
    public const REPORT_CREATED = 'report_created';
    public const DAMAGE_ID_UPDATED = 'damage_id_updated';
    public const STATUS_CHANGED = 'status_changed';
    public const REPORT_CLOSED = 'report_closed';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::REPORT_CREATED,
            self::DAMAGE_ID_UPDATED,
            self::STATUS_CHANGED,
            self::REPORT_CLOSED,
        ];
    }
}
