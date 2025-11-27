# Damage Report Management API

[![Latest Stable Version](https://img.shields.io/packagist/v/laravel/framework)](https://packagist.org/packages/laravel/framework)
[![Build Status](https://img.shields.io/github/actions/workflow/status/Dubbie/karbejelento-api/production.yaml?branch=production)](https://github.com/Dubbie/karbejelento-api/actions)

This is the Laravel backend API for the damage report management system. It provides the necessary endpoints for administrators, managers, and customers to create, view, and manage damage reports for their buildings.

## Requirements

-   [DDEV](https://docs.ddev.com/en/stable/)
-   PHP 8.3 (handled by DDEV)
-   Composer (handled by DDEV)

## Quick Start with DDEV

This project is configured to use DDEV for a consistent and simple local development environment.

1. Clone the Repository

```bash
git clone https://github.com/dubbie/karbejelento-api
cd karbejelento-api
```

2. Start DDEV

This single command will build the Docker containers, install all Composer dependencies, and start the project.

```bash
ddev start
```

_The first time you run this, it will also automatically run `composer install` for you._

3. Configure Environment & Run Migrations

DDEV automatically creates a `.env` file and configures the database connection for you. All you need to do is generate an application key and run the database migrations.

```bash
ddev artisan key:generate
ddev artisan migrate
```

4. (Optional) Seed the Database

To populate the database with sample data, run the seeder.

```bash
ddev artisan db:seed
```

Your API is now running and accessible at the URL provided by `ddev start` (usually https://karbejelento-api.ddev.site)

## Running Tests

The application has a comprehensive test suite. To run all tests within the DDEV container:

```bash
ddev artisan test
```

See `docs/notification-rules.md` for detailed guidance on configuring the notification engine.

## How to stop the project

To stop the DDEV containers:

```bash
ddev stop
```
