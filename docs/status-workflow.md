# Report Status Workflow

This guide explains how report statuses and sub-statuses behave, how transitions are enforced, and where business logic plugs into the process.

## Status Hierarchy

Statuses are defined in `App\Constants\ReportStatus` and seeded into the `statuses` table. Each status may optionally have sub-statuses (see `App\Constants\ReportSubStatus`). Examples:

- **DAMAriskhez bejelentve** (`REPORTED_TO_DAMARISK`)
- **Biztosítói kárszámra vár** (`WAITING_FOR_INSURER_DAMAGE_ID`)
- **Biztosítói ügyintézés alatt** (`UNDER_INSURER_ADMINISTRATION`)
- **Adat/irathiány** (`DATA_OR_DOCUMENT_DEFICIENCY`)
- **Lezárva** (`CLOSED`) with multiple closure sub-statuses.

Sub-statuses provide fine-grained states such as “Hiányzó irat megküldve DAMArisknek” or “Újranyitva” and are linked to a single parent status.

## Transition Flow

1. **Initiation** – Clients call `POST /api/v1/reports/{uuid}/status` with the target status/sub-status names and an optional comment/transition payload (see `ChangeStatusRequest`).
2. **Validation** – `ReportStatusTransitionService`:
   - Prevents repeating identical status/sub-status combos unless explicitly whitelisted (`UNDER_INSURER_ADMINISTRATION` with no sub-status is repeatable).
   - Runs registered rules (e.g., document request email rule, damage ID enforcement).
3. **Execution** – `ReportService::changeReportStatus`:
   - Creates a `ReportStatusHistory` row (user, status, sub-status, comment).
   - Updates `reports.status_id`, `reports.sub_status_id`, and `current_status_history_id`.
   - Dispatches notification events (status change vs report closed).
4. **Response** – Returns the fully-hydrated report resource showing the new state.

## Special Rules

Located in `app/Services/ReportStatusTransitions/Rules`:

- **`RequireDamageIdForUnderAdministrationRule`** – prevents entering “Biztosítói ügyintézés alatt” without a damage ID.
- **`SendDocumentRequestEmailRule`** – when entering `Adat/irathiány / Iratra vár ügyféltől` it:
  - Validates email payload (title, body, requested documents, attachments).
  - Writes a `DocumentRequest` + items.
  - Sends templated email(s) via `DocumentRequestMail`.

You can add more rules (e.g., to enforce attachments) by implementing `ReportStatusTransitionRule` and registering it in `AppServiceProvider`.

## Damage ID Updates

`PATCH /api/v1/reports/{uuid}/damage-id` updates the insurer’s damage identifier without changing status. Internally it still writes a status history entry (with the same status/sub-status) and triggers the `NotificationEvent::DAMAGE_ID_UPDATED` event.

## Document Requests

When a document request is sent:

- A public tokenized URL is created so claimants can upload files (`DocumentRequestPublicController`).
- Requested documents are stored in ordered `DocumentRequestItems`.
- Uploaded files attach to those items via `/public/document-requests/{request}/items/{item}/files`.

## Closing Reports

- Setting status to `Lezárva` triggers the `report_closed` notification event instead of the generic `status_changed`.
- Sub-status dictates closure reason (paid, rejected, withdrawn, duplicate, etc.).
- Status history comment is often used to capture closing notes.

## Notifications & Statuses

`ReportNotificationService` observes:

- `report_created`
- `damage_id_updated`
- `status_changed`
- `report_closed`

Each event includes metadata about the current and previous status/sub-status IDs, enabling notification rules to scope recipients to specific stages. See `docs/notification-rules.md` for configuration instructions.

## Data Visibility

- **Report lists** filter by the current `status`/`subStatus` for faster dashboards.
- **Full report payloads** embed `statusHistories` with user info, giving frontends complete context.
- **Buildings** expose `current_customer` and `current_insurer` so you can show who currently manages the workflow.

## Tips for Frontend Integrations

- Fetch statuses/sub-statuses once (e.g., on settings load) and map names to UUIDs to avoid repeated lookups.
- Always show the status history timeline alongside the current status so users can see who changed what.
- When prompting for status changes, pre-validate that required payload fields (e.g., document request email fields) are filled based on the target combination.
