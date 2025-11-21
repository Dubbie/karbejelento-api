<?php

namespace App\Constants;

final class EstimatedCost
{
    const RANGE_1 = '0-500';
    const RANGE_2 = '501-2000';
    const RANGE_3 = '2001-5000';
    const RANGE_4 = '5001+';

    public static function all(): array
    {
        return [
            self::RANGE_1,
            self::RANGE_2,
            self::RANGE_3,
            self::RANGE_4,
        ];
    }
}
