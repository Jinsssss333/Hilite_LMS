# Hilite LMS

A multi-tenant **Lead Management System (LMS)** built for HiLITE Group â€” a real estate company operating multiple brands (HiLITE Builders, HiLITE Properties). The system manages the full lifecycle of real estate sales leads, from intake through pipeline tracking, follow-up activities, and SLA monitoring.

---

## Overview

Hilite LMS is a RESTful API backend built with **Laravel 12** that supports multiple companies under a single platform. Each company has its own isolated data scope, branches, teams, users, pipeline stages, dispositions, and SLA policies.

### Key Capabilities

- **Multi-tenant architecture** â€” data is strictly scoped per company
- **Lead ingestion** from multiple sources: manual entry, CSV bulk import, and webhook (e.g. Facebook Lead Ads)
- **Pipeline management** â€” configurable stages with colour coding and ordering per company
- **Engagement tracking** â€” each leadâ€“company relationship is tracked as an engagement with a current stage and assigned salesperson
- **Activity logging** â€” calls, notes, follow-ups, and site visits are recorded  against engagements
- **SLA monitoring** â€” configurable breach thresholds per pipeline stage with escalation role targeting
- **Audit trail** â€” all significant state changes are recorded in an append-only audit log
- **Phone normalisation** â€” raw phone numbers are normalised to E.164 format using `libphonenumber` to prevent duplicates
- **Role-based access control** â€” six user roles: `super_admin`, `admin`, `manager`, `branch_head`, `team_lead`, `salesperson`
- **Token-based authentication** â€” powered by Laravel Sanctum

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Authentication | Laravel Sanctum |
| Database | SQLite (dev) / MySQL (prod-ready) |
| Queue | Laravel Queue (database driver) |
| Phone parsing | `giggsey/libphonenumber-for-php` |
| Testing | PHPUnit 11 |
| Dev tooling | Laravel Pail, Laravel Pint, Laravel Sail |Google Stitch|

---

## Project Structure

```
hilite-lms/
â”œâ”€â”€ app/
â”‚   â”œâ”€â”€ Http/
â”‚   â”‚   â”œâ”€â”€ Controllers/Api/
â”‚   â”‚   â”‚   â”œâ”€â”€ AuthController.php        # Login, logout, me
â”‚   â”‚   â”‚   â”œâ”€â”€ LeadController.php        # Lead CRUD + duplicate check
â”‚   â”‚   â”‚   â”œâ”€â”€ ImportController.php      # CSV upload & status polling
â”‚   â”‚   â”‚   â””â”€â”€ WebhookController.php     # Webhook lead intake
â”‚   â”‚   â”œâ”€â”€ Middleware/
â”‚   â”‚   â”‚   â””â”€â”€ EnforceCompanyScope.php   # Scopes all queries to company
â”‚   â”‚   â””â”€â”€ Requests/
â”‚   â”‚       â””â”€â”€ StoreLeadRequest.php
â”‚   â”œâ”€â”€ Jobs/
â”‚   â”‚   â”œâ”€â”€ ProcessImportRowJob.php       # Async CSV row processing
â”‚   â”‚   â””â”€â”€ ProcessWebhookStagingJob.php  # Async webhook lead processing
â”‚   â”œâ”€â”€ Models/
â”‚   â”‚   â”œâ”€â”€ User.php, Company.php, Branch.php, Team.php
â”‚   â”‚   â”œâ”€â”€ Lead.php, LeadEngagement.php
â”‚   â”‚   â”œâ”€â”€ PipelineStage.php, Disposition.php, SlaPolicy.php
â”‚   â”‚   â”œâ”€â”€ Activity.php, OwnershipAssignment.php
â”‚   â”‚   â””â”€â”€ AuditLog.php, ImportJob.php, StagingLead.php
â”‚   â””â”€â”€ Services/
â”‚       â”œâ”€â”€ AuditLogService.php           # Centralised audit recording
â”‚       â”œâ”€â”€ LeadIntakeService.php         # Core lead creation logic
â”‚       â””â”€â”€ PhoneNormalizationService.php # E.164 phone normalisation
â”œâ”€â”€ database/
â”‚   â”œâ”€â”€ migrations/                       # 18 migration files
â”‚   â””â”€â”€ seeders/                          # 9 seeders with demo data
â””â”€â”€ routes/
    â””â”€â”€ api.php                           # All API route definitions
```

---

## Database Schema

| Table | Purpose |
|---|---|
| `companies` | Top-level tenants |
| `branches` | Physical offices per company |
| `teams` | Sales teams within branches |
| `users` | All staff with role & team assignment |
| `leads` | Deduplicated contact records (phone E.164) |
| `lead_engagements` | Companyâ€“lead relationship with stage, owner & SLA |
| `pipeline_stages` | Configurable funnel stages per company |
| `dispositions` | Call outcome labels optionally mapped to stages |
| `sla_policies` | SLA breach days & escalation role per stage |
| `activities` | Notes, calls, follow-ups, site visits |
| `ownership_assignments` | Full ownership transfer history |
| `audit_logs` | Append-only change history |
| `import_jobs` | CSV import job tracking |
| `staging_leads` | Raw/unprocessed leads before normalisation |

---

## API Endpoints

### Public

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/login` | Authenticate and receive a Sanctum token |
| `POST` | `/api/webhooks/leads` | Ingest leads from external webhook sources |

### Protected (requires `Authorization: Bearer <token>`)

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/logout` | Revoke current token |
| `GET` | `/api/auth/me` | Get authenticated user details |
| `GET` | `/api/leads` | List leads for the company |
| `POST` | `/api/leads` | Create a new lead |
| `GET` | `/api/leads/{id}` | Get a specific lead |
| `GET` | `/api/leads/check-duplicate` | Check for existing lead by phone |
| `POST` | `/api/leads/import` | Upload a CSV file for bulk import |
| `GET` | `/api/leads/import/{uuid}/status` | Poll CSV import job status |

> All protected routes are automatically scoped to the authenticated user's company via the `company.scope` middleware.

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm
- SQLite (for local dev) or MySQL

### Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd hilite-lms

# 2. One-command setup (installs deps, copies .env, generates key, migrates DB)
composer setup

# 3. Seed the database with demo data
php artisan db:seed
```

### Running Locally

```bash
# Starts all services concurrently: HTTP server, queue worker, log viewer, Vite
composer dev
```

Or individually:

```bash
php artisan serve          # HTTP server at http://localhost:8000
php artisan queue:listen   # Queue worker for async jobs
```

### Running Tests

```bash
composer test
```

---

## Demo Users (after seeding)

> These are **local development only** accounts. Do not use these credentials in staging or production.

**Default password for all demo users: `password123`**

### HiLITE Builders

| Email | Role |
|---|---|
| `admin@hilitebuilders.com` | Admin |
| `manager@hilitebuilders.com` | Manager |
| `bh@hilitebuilders.com` | Branch Head |
| `tl.suresh@hilitebuilders.com` | Team Lead |
| `priya@hilitebuilders.com` | Salesperson |
| `kiran@hilitebuilders.com` | Salesperson |

### HiLITE Properties

| Email | Role |
|---|---|
| `admin@hiliteproperties.com` | Admin |

---

## Authentication

The API uses **Laravel Sanctum** token authentication.

```bash
# 1. Login to get a token
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@hilitebuilders.com",
  "password": "password123"
}

# 2. Use the returned token in all subsequent requests
Authorization: Bearer <your-token-here>
```

---

## Environment Configuration

Copy `.env.example` to `.env` and fill in values for your environment. **Never commit your `.env` file to version control.**

```bash
cp .env.example .env
php artisan key:generate
```

Key variables to configure:

```env
APP_ENV=local               # Change to production when deploying
APP_DEBUG=false             # Always false in production
APP_URL=                    # Your app URL

DB_CONNECTION=sqlite        # Switch to mysql for production
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

---

## License

This project is licensed under the [MIT License](LICENSE).