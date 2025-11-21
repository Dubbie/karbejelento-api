<?php

namespace App\Constants;

/**
 * Defines constants for the main (parent) statuses of a Report.
 */
final class ReportStatus
{
    /** Bejelentve */
    const REPORTED_TO_DAMARISK = 'DAMAriskhez bejelentve';

    /** Biztosítói kárszámra vár */
    const WAITING_FOR_INSURER_DAMAGE_ID = 'Biztosítói kárszámra vár';

    /** Biztosítói ügyintézés alatt */
    const UNDER_INSURER_ADMINISTRATION = 'Biztosítói ügyintézés alatt';

    /** Adat/irathiány */
    const DATA_OR_DOCUMENT_DEFICIENCY = 'Adat/irathiány';

    /** Lezárva */
    const CLOSED = 'Lezárva';

    /**
     * Returns an array of all defined main status names.
     * Useful for seeding, validation rules, etc.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::REPORTED_TO_DAMARISK,
            self::WAITING_FOR_INSURER_DAMAGE_ID,
            self::UNDER_INSURER_ADMINISTRATION,
            self::DATA_OR_DOCUMENT_DEFICIENCY,
            self::CLOSED,
        ];
    }
}
