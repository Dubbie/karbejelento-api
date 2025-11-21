<?php

namespace App\Constants;

final class ClaimantType
{
    const BUILDING = 'building';
    const RESIDENT = 'resident';

    public static function all(): array
    {
        return [
            self::BUILDING,
            self::RESIDENT,
        ];
    }
}
