<?php

namespace App\Constants;

final class ReportStatus
{
    public const NEW = 'new';
    public const WAITING_FOR_DAMAGE_ID = 'waiting_for_damage_id';
    public const WAITING_FOR_INSURER = 'waiting_for_insurer';
    public const WAITING_FOR_DOCUMENTS = 'waiting_for_documents';
    public const IN_PROGRESS = 'in_progress';
    public const CLOSED_PAID = 'closed_paid';
    public const CLOSED_DECLINED = 'closed_declined';
    public const TEMP_CLOSED_INSPECTION = 'temp_closed_inspection';
    public const TEMP_CLOSED_DOCUMENTS = 'temp_closed_documents';
    public const DELETED = 'deleted';
    public const ARCHIVED = 'archived';
    public const REOPENED = 'reopened';
}
