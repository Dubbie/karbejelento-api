<?php

namespace App\Constants;

final class NotificationRecipientType
{
    public const CUSTOM_EMAIL = 'custom_email';
    public const ROLE = 'role';
    public const REPORT_CREATOR = 'report_creator';
    public const REPORT_NOTIFIER = 'report_notifier';
    public const REPORT_CLAIMANT = 'report_claimant';
    public const BUILDING_CUSTOMER = 'building_customer';
    public const BUILDING_CUSTOMER_MANAGER = 'building_customer_manager';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::CUSTOM_EMAIL,
            self::ROLE,
            self::REPORT_CREATOR,
            self::REPORT_NOTIFIER,
            self::REPORT_CLAIMANT,
            self::BUILDING_CUSTOMER,
            self::BUILDING_CUSTOMER_MANAGER,
        ];
    }
}
