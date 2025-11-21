<?php

namespace App\Constants;

final class StreetType
{
    const AROK = 'árok';
    const ATJARO = 'átjáró';
    const DULO = 'dűlő';
    const DULOT = 'dűlőút';
    const ERDOSOR = 'erdősor';
    const FASOR = 'fasor';
    const FORDULO = 'forduló';
    const GAT = 'gát';
    const HATARSOR = 'határsor';
    const HATARUT = 'határút';
    const KAPU = 'kapu';
    const KOROND = 'körönd';
    const KORTER = 'körtér';
    const KORUT = 'körút';
    const KOZ = 'köz';
    const LAKOTELEP = 'lakótelep';
    const LEJARO = 'lejáró';
    const LEJTO = 'lejtő';
    const LEPCSO = 'lépcső';
    const LIGET = 'liget';
    const MELYUT = 'mélyút';
    const OROM = 'orom';
    const OSVENY = 'ösvény';
    const PARK = 'park';
    const PART = 'part';
    const PINCESOR = 'pincesor';
    const RAPKART = 'rakpart';
    const SETANY = 'sétány';
    const SIKATOR = 'sikátor';
    const SOR = 'sor';
    const SUGARUT = 'sugárút';
    const TER = 'tér';
    const TERE = 'tere';
    const UDVAR = 'udvar';
    const UT = 'út';
    const UTCA = 'utca';
    const UTJA = 'útja';
    const UDULOPART = 'üdülőpart';

    /**
     * Returns an array of all defined main status names.
     * Useful for seeding, validation rules, etc.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::AROK,
            self::ATJARO,
            self::DULO,
            self::DULOT,
            self::ERDOSOR,
            self::FASOR,
            self::FORDULO,
            self::GAT,
            self::HATARSOR,
            self::HATARUT,
            self::KAPU,
            self::KOROND,
            self::KORTER,
            self::KORUT,
            self::KOZ,
            self::LAKOTELEP,
            self::LEJARO,
            self::LEJTO,
            self::LEPCSO,
            self::LIGET,
            self::MELYUT,
            self::OROM,
            self::OSVENY,
            self::PARK,
            self::PART,
            self::PINCESOR,
            self::RAPKART,
            self::SETANY,
            self::SIKATOR,
            self::SOR,
            self::SUGARUT,
            self::TER,
            self::TERE,
            self::UDVAR,
            self::UT,
            self::UTCA,
            self::UTJA,
            self::UDULOPART
        ];
    }
}
