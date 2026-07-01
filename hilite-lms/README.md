# HiLITE LMS — Lead Management System

A full-stack Laravel-based Lead Management System built for HiLITE Builders. Designed for real estate sales teams with intelligent lead routing, duplicate detection, SLA tracking, role-based access control, and pipeline management.

---

## 🏗️ Architecture

### Tech Stack
| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 + Laravel 11 |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Navigation | Hotwire Turbo Drive (SPA-like speed) |
| Database | MySQL 8.x |
| Phone Parsing | Google `libphonenumber` via `giggsey/libphonenumber-for-php` |
| Auth | Custom session-based auth (no Breeze/Sanctum for web routes) |
| API Auth | Laravel Sanctum (for `/api` routes) |

### Database Schema (Hierarchy)
```
Companies
  └── Branches
        └── Teams
              └── Users (Salesperson / Team Lead / Branch Head / Manager / Admin / Super Admin)
                    └── LeadEngagements (one per company per lead)
                          └── Activities (calls, visits, follow-ups, notes)
                          └── OwnershipAssignment (full history)
```

---

## 🔐 Role System

| Role | Dashboard | Can Assign | Can Import CSV | Sees All Leads |
|------|-----------|-----------|--------------|----------------|
| `super_admin` | Manager | ✅ Company-wide | ✅ | ✅ All companies |
| `admin` | Manager | ✅ Company-wide | ✅ | ✅ Own company |
| `manager` | Manager | ✅ Company-wide | ✅ | ✅ Own company |
| `branch_head` | Manager | ✅ Branch-wide | ✅ | ✅ Own branch |
| `team_lead` | Manager | ✅ Team-wide | ✅ | ✅ Own team |
| `salesperson` | Salesperson | ❌ | ✅ | ❌ Own leads only |

---

## ✨ Core Features

### 1. Lead Intake
- **Manual Entry** — Form at `/leads/import` (Manual Entry tab). Auto-assigns new lead to the salesperson who created it.
- **Bulk CSV Import** — Upload CSV with columns: `name, phone, email, source, notes`. Available to all roles.
- **Duplicate Detection** — Phone numbers normalised to E.164 via `libphonenumber`. If a duplicate phone is submitted, the lead is **blocked from creation** and the user is shown:
  - Who currently owns it
  - A **"Request Reassignment"** modal to submit a reason to their manager
- **Race Condition Protection** — `SELECT FOR UPDATE` pessimistic lock on the `leads` table prevents duplicate creation under simultaneous requests.
- **Shared Number Support** — Phones flagged as "shared" (e.g. corporate switchboards) bypass dedup and always create fresh engagements.

### 2. Auto-Assignment Engine (`LeadIntakeService`)
- Routing policy modes: **round-robin**, **capacity-based** (load-balanced by active lead count)
- Respects per-user `max_lead_cap` (default: 50 active leads) — skips overloaded agents
- Zombie re-engagement: dormant/closed leads inactive for 60+ days are unassigned and re-pooled automatically on re-intake

### 3. Pipeline Management
- Configurable pipeline stages (Admin → Pipeline)
- Drag-free stage updates via PATCH `/leads/{id}/stage`
- SLA policies per stage — breach detection runs on engagement creation

### 4. Lead Details (`/leads/{id}`)
- Full activity log (calls, visits, notes, follow-ups) with timestamps
- Inline editing of lead name, phone, email, source, stage
- Flag phone as shared/corporate number
- Request reassignment (salesperson → team lead escalation)
- Log new activity directly from the detail view

### 5. Calendar (`/leads/calendar`)
- Monthly calendar view showing all scheduled follow-ups
- Role-scoped: salesperson sees own, team_lead sees team, managers see company
- **Schedule button** on each unscheduled lead opens a modal to pick date/time, activity type, and custom label
- Newly scheduled items appear instantly on the calendar

### 6. Follow-Ups (`/leads/followups`)
- Grouped into **Overdue**, **Today**, **Upcoming**
- Mark follow-up as complete (ownership-checked — salespersons can only complete their own)

### 7. Assignment Hub (`/dashboard/assignment`)
- Managers and above can view all **unassigned leads** for their company
- Bulk checkbox selection with floating action bar
- Assign selected leads to any salesperson with optional reason

### 8. Reports (`/reports`)
- KPIs: Total Leads, Conversion Rate, Avg Response Time, SLA Fulfillment
- Monthly intake vs conversion trend chart
- Lead source distribution
- Engagement heatmap (day × time block)
- Top 5 agent performance table (single aggregated query — no N+1)

### 9. Admin Panel (`/admin`)
- **Users** — Create, edit, activate/deactivate team members. Set `max_lead_cap` and `min_lead_floor` per user.
- **Pipeline** — Create/edit/delete pipeline stages with custom colours and order.
- **SLA Policies** — Define SLA durations per stage.
- **Audit Log** — Full audit trail of all assignment, reassignment, and intake events. CSV export.

### 10. Branding & UI
- HiLITE logo (CSS-rendered, pixel-perfect) in sidebar
- Sidebar subtitle: "Lead Management System"
- Hotwire Turbo Drive — zero full-page reloads
- Alpine.js modals, tabs, edit modes throughout
- Global top-bar search → searches leads by name, phone, or email
- Dark-themed design tokens via Tailwind custom config

---

## 🚀 Local Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Install JS dependencies
npm install

# 3. Copy environment file and configure
cp .env.example .env
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env

# 4. Generate app key
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. (Optional) Seed test data
php artisan db:seed

# 7. Build assets
npm run dev

# 8. Serve
php artisan serve
```

---

## 📁 Key File Map

| Path | Purpose |
|------|---------|
| `app/Services/LeadIntakeService.php` | Core intake, dedup, and auto-assignment logic |
| `app/Services/AssignmentService.php` | Manual assignment with role-priority enforcement |
| `app/Services/PhoneNormalizationService.php` | E.164 normalisation via libphonenumber |
| `app/Http/Controllers/LeadsController.php` | All lead CRUD + export |
| `app/Http/Controllers/CalendarController.php` | Calendar view + scheduling |
| `app/Http/Controllers/AssignmentHubController.php` | Unassigned leads hub |
| `app/Http/Controllers/ReassignmentRequestController.php` | Reassignment request escalation |
| `app/Http/Controllers/ReportsController.php` | Analytics and KPIs |
| `app/Models/LeadEngagement.php` | Core engagement model with company global scope |
| `app/Models/User.php` | User model with `isAtLeadCapacity()` helper |
| `routes/web.php` | All authenticated web routes |
| `resources/views/layouts/app.blade.php` | Master layout with sidebar + topnav |

---

## 🔒 Security Notes

- All routes are protected by `auth.lms` middleware (session check)
- All queries are scoped by `company_id` via `EnforceCompanyScope` middleware and the `LeadEngagement` global scope
- Phone numbers are always normalised before storage — no raw user input stored
- Assignment authority is enforced by role-priority — a team lead cannot override a manager's assignment
- Salespersons cannot complete other salespersons' follow-up activities (ownership checked)

---

*Last updated: 2 July 2026*
