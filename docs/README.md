# Project Documentation

Welcome to the documentation hub for the Damage Report Management API. Use this folder as the single source of truth when onboarding teammates, wiring up the frontend, or extending backend behavior.

## Contents

| Document | Description |
|----------|-------------|
| [overview.md](overview.md) | High-level architecture, domain model, key services, and API surface. |
| [status-workflow.md](status-workflow.md) | Detailed explanation of report statuses, transition rules, and document-request flow. |
| [notification-rules.md](notification-rules.md) | How to configure the notification engine, including recipient types and cURL examples. |

## Getting Started

1. Read `overview.md` to understand the moving parts.
2. Dive into `status-workflow.md` when implementing any status-related UI or automation.
3. Use `notification-rules.md` as the contract for the admin notification configuration screen.
4. For development environment setup (DDEV, migrations, testing), refer to the root `README.md`.

## Contributing to Docs

- Keep file names descriptive and linkable.
- Prefer Markdown tables for summarizing enums or recipient types.
- Reference code (classes, requests, routes) by their namespace/path for easy discovery.
- When adding new features, update or add an appropriate doc in this folder and link it from here.
