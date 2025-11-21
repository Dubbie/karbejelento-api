<?php

namespace App\Constants;

final class DamageType
{
    const SOAKING = 'Beázás';
    const BURGLARY = 'Betöréses lopás';
    const PIPE_BURST = 'Csőtörés';
    const BLOCKAGE = 'Dugulás';
    const OTHER = 'Egyéb';
    const LIABILITY = 'Felelősségbiztosítás';
    const DOWNPOUR = 'Felhőszakadás';
    const EARTHQUAKE = 'Földrengés';
    const SMOKE = 'Füst- és koromszennyezés';
    const MACHINE_FAILURE = 'Géptörés';
    const GRAFFITI = 'Graffiti';
    const SNOW = 'Hónyomás';
    const UNID_VEHICLE_ACCIDENT = 'Idegen jármű ütközése';
    const UNID_ITEM_FELLING = 'Idegen tárgy rádőlése';
    const UNKNOWN_HOLE_COLLAPSE = 'Ismeretlen üreg beomlása';
    const ICE_DAMAGE = 'Jégverés';
    const INTERCOM_DAMAGE = 'Kaputelefon rongálás';
    const PESTS = 'Kártevő okozta kár';
    const STEALING = 'Lopás';
    const PANEL_BURST = 'Panelhézag beázás';
    const EXPLOSION = 'Robbanás';
    const ROOF_STEEP = 'Tetőbeázás';
    const FIRE = 'Tűz';
    const WINDOW = 'Üvegkár';
    const VANDALISM = 'Vandalizmus';
    const STORM = 'Vihar';
    const LIGHTNING = 'Villámcsapás';
    const LOCK_REPLACEMENT = 'Zárcsere';

    /**
     * Returns an array of all defined main status names.
     * Useful for seeding, validation rules, etc.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::SOAKING,
            self::BURGLARY,
            self::PIPE_BURST,
            self::BLOCKAGE,
            self::OTHER,
            self::LIABILITY,
            self::DOWNPOUR,
            self::EARTHQUAKE,
            self::SMOKE,
            self::MACHINE_FAILURE,
            self::GRAFFITI,
            self::SNOW,
            self::UNID_VEHICLE_ACCIDENT,
            self::UNID_ITEM_FELLING,
            self::UNKNOWN_HOLE_COLLAPSE,
            self::ICE_DAMAGE,
            self::INTERCOM_DAMAGE,
            self::PESTS,
            self::STEALING,
            self::PANEL_BURST,
            self::EXPLOSION,
            self::ROOF_STEEP,
            self::FIRE,
            self::WINDOW,
            self::VANDALISM,
            self::STORM,
            self::LIGHTNING,
            self::LOCK_REPLACEMENT,
        ];
    }
}
