# HiLITE LMS — API Contracts
> **This is the shared contract. No developer deviates from these shapes without team agreement.**
> Stack: Laravel 11 · MySQL 8 · Laravel Sanctum · Redis + Horizon · giggsey/libphonenumber-for-php

---

## Global Rules

- All responses wrapped in: `{ "success": bool, "data": {}, "message": "string" }`
- All errors return: `{ "success": false, "message": "string", "errors": {} }`
- All timestamps: ISO 8601 UTC — `2026-06-13T10:30:00Z`
- All phone numbers returned in E.164 format — `+919876543210`
- All list endpoints are paginated: `{ "data": [], "meta": { "current_page", "per_page", "total" } }`
- Every request except `/auth/login` requires header: `Authorization: Bearer {token}`
- Every request except `/auth/login` and `/webhooks/*` is company-scoped automatically via middleware
- HTTP status codes: 200 success · 201 created · 400 bad input · 401 unauthenticated · 403 forbidden · 404 not found · 409 conflict (duplicate/lock) · 422 validation · 429 rate limited · 500 server error

---

## Auth

### POST /api/auth/login
**Owner: Dev A**

Request:
```json
{
  "email": "exec@hilitebuilders.com",
  "password": "secret123"
}
```

Response 200:
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 1,
      "name": "Ravi Kumar",
      "email": "exec@hilitebuilders.com",
      "role": "salesperson",
      "company_id": 1,
      "company_name": "HiLITE Builders",
      "branch_id": 2,
      "team_id": 3
    }
  },
  "message": "Login successful"
}
```

Response 401:
```json
{ "success": false, "message": "Invalid credentials", "errors": {} }
```

---

### POST /api/auth/logout
**Owner: Dev A**

Request: empty body, just the Bearer token header.

Response 200:
```json
{ "success": true, "data": {}, "message": "Logged out successfully" }
```

---

### GET /api/auth/me
**Owner: Dev A**

Response 200: same `user` object as login.

---

## Leads

### POST /api/leads
**Owner: Dev A**
Creates a lead. Normalizes phone, deduplicates, creates or attaches engagement for the requesting user's company.

Request:
```json
{
  "name": "Arjun Nair",
  "phone": "9876543210",
  "email": "arjun@email.com",
  "source": "manual",
  "notes": "Interested in 3BHK"
}
```

Response 201 (new lead created):
```json
{
  "success": true,
  "data": {
    "lead_id": 42,
    "engagement_id": 17,
    "phone_e164": "+919876543210",
    "is_duplicate": false,
    "assigned_to": null,
    "stage": "New",
    "message": "Lead created successfully"
  },
  "message": "Lead created"
}
```

Response 200 (duplicate — existing lead re-engaged by same company):
```json
{
  "success": true,
  "data": {
    "lead_id": 42,
    "engagement_id": 17,
    "phone_e164": "+919876543210",
    "is_duplicate": true,
    "assigned_to": {
      "id": 5,
      "name": "Priya S"
    },
    "stage": "Contacted",
    "message": "Lead already exists and is assigned to Priya S"
  },
  "message": "Duplicate lead attached"
}
```

Response 409 (lead locked — already owned by another exec in same company, requestor is not TL/Manager):
```json
{
  "success": false,
  "message": "This lead is currently assigned to Priya S. Contact your Team Lead to reassign.",
  "errors": {}
}
```

---

### GET /api/leads
**Owner: Dev A**
Returns paginated list of engagements for the current company (NOT global leads).

Query params:
- `search` — searches name, phone, email
- `stage_id` — filter by stage
- `assigned_to` — filter by user id
- `source` — manual · csv · webhook
- `status` — active · dormant · closed
- `per_page` — default 25, max 100
- `page`

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "engagement_id": 17,
      "lead_id": 42,
      "name": "Arjun Nair",
      "phone_e164": "+919876543210",
      "email": "arjun@email.com",
      "source": "manual",
      "stage": { "id": 1, "name": "New", "color": "#6366f1" },
      "assigned_to": { "id": 5, "name": "Priya S" },
      "last_activity_at": "2026-06-10T14:22:00Z",
      "sla_due_at": "2026-06-15T00:00:00Z",
      "sla_breached": false,
      "created_at": "2026-06-08T09:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 340 }
}
```

---

### GET /api/leads/{engagement_id}
**Owner: Dev A**
Returns full detail of one engagement (company-scoped).

Response 200:
```json
{
  "success": true,
  "data": {
    "engagement_id": 17,
    "lead_id": 42,
    "name": "Arjun Nair",
    "phone_e164": "+919876543210",
    "email": "arjun@email.com",
    "source": "manual",
    "stage": { "id": 1, "name": "New", "color": "#6366f1" },
    "assigned_to": { "id": 5, "name": "Priya S" },
    "last_activity_at": "2026-06-10T14:22:00Z",
    "sla_due_at": "2026-06-15T00:00:00Z",
    "sla_breached": false,
    "created_at": "2026-06-08T09:00:00Z",
    "activities": [
      {
        "id": 1,
        "type": "note",
        "disposition": null,
        "notes": "Called, interested in 3BHK",
        "follow_up_at": null,
        "created_by": { "id": 5, "name": "Priya S" },
        "created_at": "2026-06-10T14:22:00Z"
      }
    ],
    "assignment_history": [
      {
        "assigned_to": { "id": 5, "name": "Priya S" },
        "assigned_by": { "id": 2, "name": "TL Suresh" },
        "reason": "Initial assignment",
        "assigned_at": "2026-06-08T09:00:00Z"
      }
    ]
  }
}
```

---

### GET /api/leads/check-duplicate
**Owner: Dev A**
Pre-check before form submission. Frontend calls this on phone blur.

Query params: `phone=9876543210`

Response 200:
```json
{
  "success": true,
  "data": {
    "exists": true,
    "engagement_exists_in_company": true,
    "assigned_to": { "id": 5, "name": "Priya S" },
    "stage": "Contacted"
  }
}
```

---

## Pipeline Stages

### GET /api/pipeline-stages
**Owner: Dev B**

Response 200:
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "New", "order": 1, "color": "#6366f1", "sla_days": 2, "is_closed": false },
    { "id": 2, "name": "Contacted", "order": 2, "color": "#f59e0b", "sla_days": 5, "is_closed": false },
    { "id": 3, "name": "Interested", "order": 3, "color": "#10b981", "sla_days": 7, "is_closed": false },
    { "id": 4, "name": "Site Visit Scheduled", "order": 4, "color": "#3b82f6", "sla_days": 3, "is_closed": false },
    { "id": 5, "name": "Negotiation", "order": 5, "color": "#8b5cf6", "sla_days": 10, "is_closed": false },
    { "id": 6, "name": "Booked", "order": 6, "color": "#22c55e", "sla_days": null, "is_closed": true },
    { "id": 7, "name": "Lost", "order": 7, "color": "#ef4444", "sla_days": null, "is_closed": true },
    { "id": 8, "name": "Not Interested", "order": 8, "color": "#6b7280", "sla_days": null, "is_closed": true }
  ]
}
```

---

### PATCH /api/engagements/{engagement_id}/stage
**Owner: Dev B**

Request:
```json
{
  "stage_id": 3,
  "disposition_id": 2,
  "notes": "Very interested, wants 3BHK on 5th floor"
}
```

Response 200:
```json
{
  "success": true,
  "data": {
    "engagement_id": 17,
    "previous_stage": { "id": 1, "name": "New" },
    "current_stage": { "id": 3, "name": "Interested" },
    "sla_due_at": "2026-06-20T00:00:00Z"
  },
  "message": "Stage updated"
}
```

Response 403:
```json
{ "success": false, "message": "You do not own this lead. Contact your Team Lead.", "errors": {} }
```

---

## Dispositions

### GET /api/dispositions
**Owner: Dev B**

Query params: `stage_id=3` (optional — filters dispositions relevant to a stage)

Response 200:
```json
{
  "success": true,
  "data": [
    { "id": 1, "label": "No Answer", "stage_id": null },
    { "id": 2, "label": "Callback Requested", "stage_id": null },
    { "id": 3, "label": "Interested", "stage_id": 3 },
    { "id": 4, "label": "Not Interested", "stage_id": null },
    { "id": 5, "label": "Site Visit Confirmed", "stage_id": 4 },
    { "id": 6, "label": "Price Negotiation", "stage_id": 5 },
    { "id": 7, "label": "Deal Closed", "stage_id": 6 },
    { "id": 8, "label": "Lost — Budget", "stage_id": 7 },
    { "id": 9, "label": "Lost — Competitor", "stage_id": 7 }
  ]
}
```

---

## Activities

### POST /api/engagements/{engagement_id}/activities
**Owner: Dev B**

Request:
```json
{
  "type": "followup",
  "disposition_id": 2,
  "notes": "Client asked to call back Thursday",
  "follow_up_at": "2026-06-16T10:00:00Z"
}
```

`type` allowed values: `note` · `followup` · `visit` · `call`

Response 201:
```json
{
  "success": true,
  "data": {
    "id": 55,
    "engagement_id": 17,
    "type": "followup",
    "disposition": { "id": 2, "label": "Callback Requested" },
    "notes": "Client asked to call back Thursday",
    "follow_up_at": "2026-06-16T10:00:00Z",
    "created_by": { "id": 5, "name": "Priya S" },
    "created_at": "2026-06-13T11:00:00Z"
  },
  "message": "Activity logged"
}
```

---

### GET /api/activities/upcoming
**Owner: Dev B**
Returns follow-ups due for the authenticated user.

Query params: `days=7` (default 7)

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "activity_id": 55,
      "engagement_id": 17,
      "lead_name": "Arjun Nair",
      "phone_e164": "+919876543210",
      "type": "followup",
      "notes": "Client asked to call back Thursday",
      "follow_up_at": "2026-06-16T10:00:00Z",
      "stage": "Contacted",
      "overdue": false
    }
  ]
}
```

---

## Assignment

### PATCH /api/engagements/{engagement_id}/assign
**Owner: Dev C**
TL assigns within team. Manager assigns across teams. Admin assigns anywhere.

Request:
```json
{
  "assign_to_user_id": 7,
  "reason": "Priya is on leave"
}
```

Response 200:
```json
{
  "success": true,
  "data": {
    "engagement_id": 17,
    "assigned_to": { "id": 7, "name": "Kiran M" },
    "assigned_by": { "id": 2, "name": "TL Suresh" },
    "reason": "Priya is on leave",
    "assigned_at": "2026-06-13T12:00:00Z"
  },
  "message": "Lead assigned successfully"
}
```

Response 403:
```json
{ "success": false, "message": "You can only assign leads within your team.", "errors": {} }
```

---

### GET /api/users/assignable
**Owner: Dev C**
Returns users the current user is allowed to assign leads to.

Response 200:
```json
{
  "success": true,
  "data": [
    { "id": 5, "name": "Priya S", "role": "salesperson", "active_leads": 12 },
    { "id": 7, "name": "Kiran M", "role": "salesperson", "active_leads": 8 }
  ]
}
```

---

## CSV Bulk Upload

### POST /api/leads/import
**Owner: Dev A**
Accepts CSV file, dumps to staging table, returns job ID. Queue does the rest.

Request: `multipart/form-data`
- `file` — CSV file
- `source` — string label e.g. `"csv_import_june"`

Response 202:
```json
{
  "success": true,
  "data": {
    "job_id": "uuid-here",
    "total_rows": 120,
    "status": "queued",
    "status_url": "/api/leads/import/uuid-here/status"
  },
  "message": "File received. Processing in background."
}
```

---

### GET /api/leads/import/{job_id}/status
**Owner: Dev A**

Response 200:
```json
{
  "success": true,
  "data": {
    "job_id": "uuid-here",
    "status": "processing",
    "total_rows": 120,
    "processed": 74,
    "created": 60,
    "duplicates_attached": 10,
    "failed": 4,
    "errors": [
      { "row": 12, "reason": "Invalid phone number" },
      { "row": 34, "reason": "Missing name" }
    ]
  }
}
```

---

## Webhook Intake

### POST /api/webhooks/leads
**Owner: Dev A**
Unauthenticated endpoint (secured by `X-Webhook-Key` header). Accepts lead from external source.

Headers: `X-Webhook-Key: {key_from_env}`

Request:
```json
{
  "name": "Suresh K",
  "phone": "+919123456789",
  "email": "suresh@email.com",
  "source": "meta_ads",
  "company_slug": "hilite-builders",
  "meta": {
    "campaign": "June-3BHK-Ads",
    "ad_id": "ad_abc123"
  }
}
```

Response 202:
```json
{
  "success": true,
  "data": { "queued": true },
  "message": "Lead received"
}
```

---

## Audit Log

### GET /api/audit-logs
**Owner: Dev C**
Admin/Manager only.

Query params: `engagement_id` · `user_id` · `action` · `from_date` · `to_date`

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "engagement_id": 17,
      "action": "stage_changed",
      "actor": { "id": 5, "name": "Priya S" },
      "before": { "stage": "New" },
      "after": { "stage": "Contacted" },
      "created_at": "2026-06-10T14:22:00Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 8 }
}
```

`action` values: `lead_created` · `duplicate_attached` · `stage_changed` · `assigned` · `reassigned` · `activity_logged` · `sla_breached` · `lead_dormant`

---

## SLA / Routing (Admin only)

### GET /api/admin/sla-policies
**Owner: Dev C**

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "stage_id": 1,
      "stage_name": "New",
      "sla_days": 2,
      "escalate_to_role": "team_lead",
      "company_id": 1
    }
  ]
}
```

### PATCH /api/admin/sla-policies/{id}
**Owner: Dev C**

Request:
```json
{ "sla_days": 3 }
```

Response 200: updated policy object.

---

## Users (Admin only)

### GET /api/admin/users
**Owner: Dev C**

Response 200:
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Priya S",
      "email": "priya@hilitebuilders.com",
      "role": "salesperson",
      "branch_id": 2,
      "team_id": 3,
      "active_leads": 12,
      "company_id": 1
    }
  ]
}
```

---

## Shared Enums (hardcode these exactly across all three devs)

```
roles:        super_admin · admin · manager · branch_head · team_lead · salesperson
sources:      manual · csv · webhook · callsync_auto
activity_types: note · followup · call · visit
audit_actions: lead_created · duplicate_attached · stage_changed · assigned · reassigned · activity_logged · sla_breached · lead_dormant
lead_status:  active · dormant · closed
```

---

## CSV Import Format
Exec uploads a CSV with these exact column headers (case-insensitive):
```
name, phone, email, source, notes
```
Any other columns are ignored. Rows with missing `name` or `phone` are failed and reported.

---

*Last updated: 2026-06-13 — any change to this file requires all three developers to agree.*
