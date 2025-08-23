<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @mixin \App\Traits\Paginatable
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $postcode
 * @property string $city
 * @property string $street_name
 * @property string|null $street_type
 * @property string $street_number
 * @property string $bond_number
 * @property string|null $account_number
 * @property string|null $insurer
 * @property bool $is_archived
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $current_customer
 * @property-read string $formatted_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BuildingManagement> $managementHistory
 * @property-read int|null $management_history_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building forUser(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building performAdvancedPagination(\Illuminate\Http\Request $request, array $options)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereBondNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereInsurer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereIsArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereStreetName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereStreetNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereStreetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Building whereUuid($value)
 */
	class Building extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $building_id
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\Building $building
 * @property-read \App\Models\User $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement whereBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BuildingManagement whereStartDate($value)
 */
	class BuildingManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property string $name
 * @property string $email
 * @property string|null $phone_number
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifier whereUpdatedAt($value)
 */
	class Notifier extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property int $building_id
 * @property int $created_by_user_id
 * @property int $notifier_id
 * @property string $bond_number
 * @property string $insurer
 * @property string|null $damage_id
 * @property string $damage_type
 * @property string $damage_description
 * @property string|null $damaged_building_name
 * @property string|null $damaged_building_number
 * @property string|null $damaged_floor
 * @property string|null $damaged_unit_or_door
 * @property \Illuminate\Support\Carbon $damage_date
 * @property string|null $estimated_cost
 * @property string $current_status
 * @property string $claimant_type
 * @property string|null $claimant_name
 * @property string|null $claimant_email
 * @property string|null $claimant_phone_number
 * @property string|null $contact_name
 * @property string|null $contact_phone_number
 * @property string|null $claimant_account_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \App\Models\Building $building
 * @property-read \App\Models\User $createdBy
 * @property-read \App\Models\Notifier $notifier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportStatusHistory> $statusHistory
 * @property-read int|null $status_history_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereBondNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereBuildingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereClaimantAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereClaimantEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereClaimantName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereClaimantPhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereClaimantType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereContactPhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCurrentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamageDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamageDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamagedBuildingName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamagedBuildingNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamagedFloor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereDamagedUnitOrDoor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereEstimatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereInsurer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereNotifierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereUuid($value)
 */
	class Report extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property int $report_id
 * @property int $uploaded_by_user_id
 * @property string $file_path
 * @property string $file_name_original
 * @property string $file_mime_type
 * @property int $file_size_bytes
 * @property string $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\Report $report
 * @property-read \App\Models\User $uploadedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereFileMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereFileNameOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereFileSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereUploadedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereUuid($value)
 */
	class ReportAttachment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property string|null $updated_at
 * @property-read \App\Models\Report|null $report
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportStatusHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportStatusHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportStatusHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportStatusHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportStatusHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportStatusHistory whereUpdatedAt($value)
 */
	class ReportStatusHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @mixin \App\Traits\Paginatable
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property int $is_active
 * @property int|null $manager_id
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User performAdvancedPagination(\Illuminate\Http\Request $request, array $options)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

