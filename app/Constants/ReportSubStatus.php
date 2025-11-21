<?php

namespace App\Constants;

/**
 * Defines constants for the sub-statuses of a Report.
 * The naming convention PARENT_ACTION helps identify their context.
 */
final class ReportSubStatus
{
    //region Biztosítói ügyintézés alatt
    const ADMIN_INSURER_SETTLEMENT_IN_PROGRESS = 'Biztosítói kárrendezés folyamatban';
    const ADMIN_AWAITING_INSPECTOR_CONTACT = 'Kármegoldó helyszíni szemle- kapcsolatfelvételre vár';
    const ADMIN_AWAITING_INSPECTOR_INSPECTION = 'Kármegoldó helyszíni szemle- szemlére vár';
    const ADMIN_AWAITING_INSPECTOR_CLOSURE = 'Kármegoldó helyszíni szemle- lezárásra vár';
    const ADMIN_REVIEW_REQUEST_SUBMITTED = 'Felülvizsgálati kérelem beadva biztosítónak';
    const ADMIN_SUPPLEMENTARY_DOCUMENT_SENT = 'Pótdokumentum megküldve biztosítónak';
    const ADMIN_REOPENED = 'Újranyitva';
    //endregion

    //region Adat/irathiány
    const DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT = 'Iratra vár ügyféltől';
    const DEFICIENCY_TEMP_CLOSED_INSPECTION = 'Szemlehiány miatt ideiglenesen lezárva';
    const DEFICIENCY_TEMP_CLOSED_DOCUMENT = 'Irathiány miatt ideiglenesen lezárva';
    const DEFICIENCY_DOCUMENT_SENT_TO_DAMARISK = 'Hiányzó irat megküldve DAMArisknek';
    //endregion

    //region Lezárva
    const CLOSED_WITH_PAYMENT = 'Kifizetéssel';
    const CLOSED_WITH_REJECTION = 'Elutasítással';
    const CLOSED_CLAIM_WITHDRAWN = 'Visszavont kárigény';
    const CLOSED_INCORRECT_REPORT = 'Téves bejelentés';
    const CLOSED_DUPLICATE_REPORT = 'Dupla bejelentés';
    const CLOSED_DUE_TO_INDIFFERENCE = 'Érdektelenséggel lezárva';
    const CLOSED_DELETED = 'Törölve';
    const CLOSED_ARCHIVED = 'Archiválva';
    //endregion

    /**
     * Returns an array of all defined sub-status names.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::ADMIN_INSURER_SETTLEMENT_IN_PROGRESS,
            self::ADMIN_AWAITING_INSPECTOR_CONTACT,
            self::ADMIN_AWAITING_INSPECTOR_INSPECTION,
            self::ADMIN_AWAITING_INSPECTOR_CLOSURE,
            self::ADMIN_REVIEW_REQUEST_SUBMITTED,
            self::ADMIN_SUPPLEMENTARY_DOCUMENT_SENT,
            self::ADMIN_REOPENED,
            self::DEFICIENCY_WAITING_FOR_DOCUMENT_FROM_CLIENT,
            self::DEFICIENCY_TEMP_CLOSED_INSPECTION,
            self::DEFICIENCY_TEMP_CLOSED_DOCUMENT,
            self::DEFICIENCY_DOCUMENT_SENT_TO_DAMARISK,
            self::CLOSED_WITH_PAYMENT,
            self::CLOSED_WITH_REJECTION,
            self::CLOSED_CLAIM_WITHDRAWN,
            self::CLOSED_INCORRECT_REPORT,
            self::CLOSED_DUPLICATE_REPORT,
            self::CLOSED_DUE_TO_INDIFFERENCE,
            self::CLOSED_DELETED,
            self::CLOSED_ARCHIVED,
        ];
    }
}
