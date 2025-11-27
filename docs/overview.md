# System Overview

This document summarizes how the Damage Report Management API is structured, the core domain objects it exposes, and the supporting services you will interact with while building the frontend.

## Goal

The API centralizes insurance damage reports for managed buildings. It tracks where each report comes from, who should act on it, which insurer is involved, and how the workflow progresses until closure. Along the way the system keeps a full status history, handles document requests, and notifies stakeholders via the configurable notification engine.

## Architecture at a Glance

- **Laravel 11 API** – exposes REST-style endpoints under `/api/v1/*`.
- **Sanctum Authentication** – session or token-based auth for SPAs and API clients.
- **Role Middleware** – custom middleware restricts routes to `admin`, `manager`, `damage_solver`, or `customer` roles.
- **DDEV Environment** – local development runs in Docker via DDEV (see root `README` for setup).
- **Database** – PostgreSQL/MySQL compatible schema managed through Laravel migrations and factories for tests.

All controllers live in `app/Http/Controllers/Api`, with validation handled by form request classes in `app/Http/Requests`. Responses typically use JSON resources for consistent structure (see `app/Http/Resources`).

## Key Domain Concepts

| Model | Description | Notes |
|-------|-------------|-------|
| `User` | Application user with `role`, optional `manager_id`, and activity flag. | Relationships: `customers`, `manager`, `notifiers`. |
| `Building` | A managed property with address metadata and insurer link. | Has many `managementHistory` records that define which customer controls the building over time. |
| `BuildingManagement` | Time-bounded association between a building, a customer, and optionally an insurer. | Determines which customer receives building-specific notifications. |
| `Notifier` | A person who can file reports for a customer. | Often the starting contact for report creation. |
| `Report` | Core entity describing a damage event. | Tracks building, notifier, insurance details, claimants, attachments, and current `status`/`subStatus`. |
| `Status` / `SubStatus` | Workflow stage and optional refinement. | Names are user-facing (Hungarian) constants; see `docs/status-workflow.md`. |
| `ReportStatusHistory` | Immutable log capturing every status (and sub-status) change with actor/comment. | `reports.current_status_history_id` points at the latest entry. |
| `DocumentRequest` (+items/files) | Structured request for missing documents from a claimant. | Sent via email with optional upload link. |
| `NotificationRule` | Configurable rule that maps workflow events to recipient lists. | See `docs/notification-rules.md`. |

## Authentication & Roles

- **Sanctum tokens** secure API access. Users authenticate via `/api/v1/auth/login`.
- The `role` middleware (`bootstrap/app.php`) enforces RBAC on sensitive routes (e.g., only admins can manage users, only admins/managers/damage solvers can mutate reports).
- Roles: `admin`, `manager`, `damage_solver`, `customer`. Status-change endpoints are limited to `admin`, `manager`, `damage_solver`.

## Workflow Highlights

1. **Report creation** (`POST /api/v1/reports`) requires a building UUID and notifier UUID, then sets the default status (`DAMAriskhez bejelentve`).
2. **Status transitions** go through `ReportStatusTransitionService`, which validates allowed repeats and runs extra rules (damage ID requirement, document-request emails).
3. **Document requests** occur when transitioning to `Adat/irathiány / Iratra vár ügyféltől`. The transition request carries email copy, requested documents, and attachments.
4. **Status change history** is always written, even when updating damage IDs (without altering status).
5. **Notifications** fire whenever reports are created, damage IDs change, statuses change, or reports close. Rules determine who receives which event (see notifications doc).

For a detailed walkthrough of workflow logic, see `docs/status-workflow.md`.

## API Surface (selected)

| Controller | Routes (prefix `/api/v1`) | Purpose |
|------------|--------------------------|---------|
| `AuthController` | `POST /auth/login` | Issue Sanctum tokens. |
| `BuildingController` | `/buildings` | CRUD buildings, list reports/notifiers for a building. |
| `ReportController` | `/reports` | Create, view, update reports, upload attachments, change status, update damage ID. |
| `DocumentRequestPublicController` | `/public/document-requests` | Public-facing endpoints for claimants to view/upload documents. |
| `UserController`, `InsurerController` | `/users`, `/insurers` | Admin-facing management endpoints. |
| `NotificationRuleController` | `/notification-rules` | Admin UI for configuring email rules. |

The feature tests (`tests/Feature/*`) demonstrate the expected responses and are useful references for frontend contracts.

## Supporting Services

- **`ReportService`** – single point of truth for creating reports, adding attachments, updating damage IDs, and mutating statuses.
- **`ReportStatusTransitionService`** – enforces workflow rules and delegates to specific transition rules (`ReportStatusTransitions/Rules/*`).
- **`ReportNotificationService`** – looks up notification rules, resolves recipients, and dispatches emails.
- **`PaginationService`** – shared `advancedPaginate` macro for listing endpoints.

## Testing & Quality

- PHPUnit + Pest-style tests live under `tests/Feature` and `tests/Unit`.
- `phpunit.xml` configures an in-memory SQLite database, Sanctum, and array mailer.
- Factories exist for every model, enabling isolated tests.
- CI (GitHub Actions) runs the test suite on pushes (see project repo for workflow file).

## Where to Go Next

- **Workflow specifics:** `docs/status-workflow.md`
- **Notification configuration:** `docs/notification-rules.md`
- **Setup instructions & DDEV usage:** root `README.md`
