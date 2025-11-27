# Notification Rules Guide

The notification engine is entirely API-driven, allowing administrators to set up bespoke delivery rules without modifying code. This guide explains how to query status metadata, structure rule payloads, and test the resulting emails.

## Endpoints

All routes live under `/api/v1/notification-rules` and require:

- A valid Sanctum token.
- `admin` role (middleware-enforced).
- `Accept: application/json` and `Content-Type: application/json` headers.

| Method & Path | Description |
|---------------|-------------|
| `GET /api/v1/notification-rules` | List rules. |
| `GET /api/v1/notification-rules/{rule:uuid}` | Retrieve one rule. |
| `POST /api/v1/notification-rules` | Create a new rule. |
| `PATCH /api/v1/notification-rules/{rule:uuid}` | Update an existing rule. |
| `DELETE /api/v1/notification-rules/{rule:uuid}` | Delete a rule. |

## Core Concepts

### Events

`event` must be one of:

- `report_created`
- `damage_id_updated`
- `status_changed`
- `report_closed` (fires instead of `status_changed` when the new status equals `Lezárva`)

### Status and Sub-status Filters

Rules may target every occurrence of an event, or be narrowed to a specific status/sub-status:

- `status_uuid`: optional, matches the parent status.
- `sub_status_uuid`: optional. If set, it must belong to the provided `status_uuid`.

To find the UUIDs:

```bash
curl -H "Authorization: Bearer <token>" \
  https://karbejelento-api.ddev.site/api/v1/statuses
```

Use a similar request for `/api/v1/sub-statuses` or inspect the status payloads returned from the report endpoints.

### Recipients

Each rule must declare at least one recipient object with:

- `type` – determines how the email target is resolved.
- `value` – only needed for some types.

| Type | Value Required | Behavior |
|------|----------------|----------|
| `custom_email` | Yes (`user@example.com`) | Sends to the provided address. |
| `role` | Yes (`admin`, `manager`, `damage_solver`, `customer`) | Sends to every active user with that role. |
| `report_creator` | No | Sends to the user who created the report. |
| `report_notifier` | No | Sends to the notifier’s email. |
| `report_claimant` | No | Sends to the claimant email captured on the report. |
| `building_customer` | No | Sends to the customer currently managing the report’s building. |
| `building_customer_manager` | No | Sends to that customer’s manager. |

The notification service deduplicates email addresses across all recipients in the rule.

### Payload Reference

```json
{
  "name": "Human readable label",
  "event": "status_changed",
  "status_uuid": "optional-status-uuid",
  "sub_status_uuid": "optional-sub-status-uuid",
  "is_active": true,
  "recipients": [
    {"type": "report_claimant"},
    {"type": "role", "value": "damage_solver"}
  ]
}
```

## Examples

### 1. Notify Customers When Waiting for Insurer

```bash
curl -X POST https://karbejelento-api.ddev.site/api/v1/notification-rules \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Customer alert: waiting for insurer",
    "event": "status_changed",
    "status_uuid": "UUID-FOR-Biztosítói ügyintézés alatt",
    "recipients": [
      {"type": "report_claimant"},
      {"type": "report_notifier"}
    ]
  }'
```

### 2. Ping Damage Solvers When Documents Go to DAMArisk

```bash
curl -X POST https://karbejelento-api.ddev.site/api/v1/notification-rules \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Solver alert: docs sent to DAMArisk",
    "event": "status_changed",
    "status_uuid": "UUID-FOR-Adat/irathiány",
    "sub_status_uuid": "UUID-FOR-Hiányzó irat megküldve DAMArisknek",
    "recipients": [
      {"type": "role", "value": "damage_solver"}
    ]
  }'
```

### 3. Broadcast Closure Emails to Admins

```bash
curl -X POST https://karbejelento-api.ddev.site/api/v1/notification-rules \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin closure digest",
    "event": "report_closed",
    "recipients": [
      {"type": "role", "value": "admin"}
    ]
  }'
```

### 4. Manager Loop on Damage-ID Updates

```bash
curl -X POST https://karbejelento-api.ddev.site/api/v1/notification-rules \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Manager FYI: damage ID changes",
    "event": "damage_id_updated",
    "recipients": [
      {"type": "building_customer_manager"}
    ]
  }'
```

### 5. Custom Email for Specific Building Partners

```bash
curl -X POST https://karbejelento-api.ddev.site/api/v1/notification-rules \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "External partner on reopened cases",
    "event": "status_changed",
    "status_uuid": "UUID-FOR-Biztosítói ügyintézés alatt",
    "sub_status_uuid": "UUID-FOR-Újranyitva",
    "recipients": [
      {"type": "custom_email", "value": "partner@example.com"}
    ]
  }'
```

## Operational Tips

1. **Draft then Activate** – create rules with `"is_active": false`, test them via staging data, then PATCH to `true`.
2. **Audit** – periodically `GET /api/v1/notification-rules` and store the JSON in version control or a shared Drive for auditability.
3. **Avoid Duplication** – multiple rules can target the same event; keep names descriptive and avoid overlapping audiences unless needed.
4. **Monitor Mail Logs** – tie rule names to SMTP logs for quick correlation.
5. **Queues** – for high volume events, consider queueing the `Mail::send` calls (swap to queued mailables) if throughput becomes an issue.
