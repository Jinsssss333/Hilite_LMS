# HiLITE Lead Management System — Data Model Proposal (v0.1, for review)

> First-draft architecture, informed by deep research into Salesforce/HubSpot/Zoho patterns
> and MySQL best practices. **Nothing here is final** — it's for the user to review and
> refine before any code. Plain-language explanations included so it can be read without
> coding knowledge.
>
> Maps onto the team's original mental model:
> **Leads → `leads` (master)** · **Leads History → `lead_engagements`** ·
> **Lead Follow Up → `follow_ups`**.
>
> **Database platform: MySQL 8.x** (client direction — decision #19). Two MySQL-specific
> adjustments from the original PostgreSQL draft: (1) tenant isolation is enforced in the
> **application layer** (a global `company_id` query scope) because MySQL has no row-level
> security — decision #20; (2) analytics use **summary/rollup tables** or **OCI MySQL HeatWave**
> instead of materialized views — decision #21. Deduplication, partitioning, indexing, read
> replicas and connection pooling all work natively in MySQL 8.

---

## 1. The big picture (how the pieces fit)

```
                          ┌──────────────────────────────────────────┐
   SHARED / GLOBAL LAYER  │   leads (MASTER — one row per person)      │   ← unique by phone_e164
   (no tenant isolation;  │   lead_phones · lead_links (households)     │     group-wide, unpartitioned
    the single source     └──────────────────────┬─────────────────────┘
    of truth)                                     │ 1 person : many company engagements
                                                  │
   TENANT-ISOLATED LAYER  ┌──────────────────────▼─────────────────────┐
   (every row tagged by   │   lead_engagements ("Leads History")        │   ← company_id + app scope
    company_id; app layer │   = one company's relationship with a lead   │
    hides other            │     ├── ownership_assignments (SCD2 history)│
    companies' rows)       │     └── follow_ups / activities (tasks)     │   ← partitioned by month
                          └──────────────────────┬─────────────────────┘
                                                  │ attributed to
   MARKETING / SOURCING   ┌──────────────────────▼─────────────────────┐
                          │   sources · campaigns · campaign_costs       │
                          │   ingestion_keys (per-campaign lead API)     │
                          └──────────────────────────────────────────────┘

   ORG / ACCESS           companies · branches · teams · users · roles · user_assignments (SCD2)
   GOVERNANCE             pipeline_stages · dispositions · sla_policies · routing_rules
   TRUST                  audit_logs · consents · (soft deletes everywhere)
   COMMS (phased)         communications (WhatsApp / SMS / call / email)
```

**The one idea that makes everything work:** a person (`leads`) is **global and shared**; a
company's *relationship* with that person (`lead_engagements`) is **private to that company**.
Group roles (Super Admin / Group CEO) can see across all companies; company roles see only
their own engagements. Two companies can each have their own engagement with the *same* master
person without seeing each other's — exactly the rule we agreed (option a).

---

## 2. Cluster A — Organisation & access

| Table | Purpose / key columns |
|---|---|
| `companies` | The tenants (HiLITE Builders, HiLITE Properties, future Dubai co). `country`, `timezone`, `currency`, `default_language`, `working_calendar`. Drives per-company time/currency behaviour. |
| `branches` | Regional branches under a company. |
| `teams` | Teams under a branch (a TL + salespeople). |
| `users` | Staff accounts. Login, contact, status. |
| `roles` | CEO, Sales Head, Branch Head (BH), Team Lead (TL), Salesperson, SBM, Super Admin. Permissions attached here. |
| `user_assignments` | **Effective-dated (SCD2):** which company/branch/team/role a user holds, `valid_from`/`valid_to`. A staff move = close the old row, open a new one. History of "who was where, when" is never lost. |

**Why SCD2 here:** when a salesperson moves branches/companies, past records still correctly
show who handled what *at the time*, while current access follows the new assignment.

---

## 3. Cluster B — Lead identity (the global master)

| Table | Purpose / key columns |
|---|---|
| `leads` (MASTER) | **One row per unique human.** `phone_raw`, **`phone_e164` (UNIQUE, indexed)**, `country`, golden `name`/`email`, `created_at`. **Unpartitioned** so the global unique-phone rule holds. This is the dedup anchor. |
| `lead_phones` | Additional phone numbers belonging to the same master (a person with multiple numbers). Each normalized to E.164; a number maps to exactly one master. |
| `lead_links` | **Manual "household / same person" associations** between two masters (e.g., husband's and wife's numbers). Links, never destroys — each master keeps its own history. Optional `relationship` + who linked it. |

**Deduplication rule (enforced by the database, not just the app):**
- Incoming phone → normalized to `phone_e164` → if it already exists, **attach** to that master
  (new engagement/history row); if not, create a new master. A `UNIQUE` constraint on
  `phone_e164` makes a duplicate master **physically impossible**, even under simultaneous
  submissions (the concurrency fix).
- Different numbers are different masters by default; joining them is a **manual link**
  (`lead_links`), optionally suggested by name/email similarity for a human to confirm.
- **Golden record:** the master's displayed name/email is chosen **field-by-field** (best
  value across all engagements — most recent / most complete), per the research.

---

## 4. Cluster C — Engagement & ownership (tenant-isolated "history")

| Table | Purpose / key columns |
|---|---|
| `lead_engagements` ("Leads History") | One company's relationship with a master lead. `company_id` (**tenant key**), `lead_id`→master, `source_id`, `campaign_id`, **`provenance`** (`company_sourced` / `rep_self_sourced`), **owner path: `assigned_branch_id` · `assigned_team_id` · `assigned_user_id`** (nullable, filled top-down; deepest non-null = responsible party; all-null = Unassigned/global pool), `stage_id`, `disposition_id`, `created_at`, `last_activity_at`. |
| `ownership_assignments` | **SCD2 history of the owner path** and when. `level` (branch/team/sp), `target_id`, `valid_from`/`valid_to`, `reason` (new/round-robin/weighted/capacity/attribute/claim/transfer/reassign/staff-exit), `assigned_by`. Drives the transfer/allocation screens + audit of "who got the lead, at what level, when". |
| `follow_ups` / `activities` | Tasks/calls/site-visits/notes against an engagement. References **`lead_id` + `engagement_id`** (matches the team's "Lead Follow Up" idea). High volume → **partitioned by month** *(MySQL note: InnoDB disallows foreign keys on partitioned tables, so this relation is enforced at the application layer + the partition key is carried in the PK)*. Polymorphic `subject_type/subject_id` so any activity type attaches cleanly. |
| `call_logs` | **Built-in call logging.** Salesperson calls sync in here: `company_id`, `lead_id`, `engagement_id`, `user_id` (caller), `direction` (in/out), `phone_dialed`, `started_at`/`answered_at`/`ended_at`, `duration`, `recording_url`, `disposition_id`, provider/raw payload. Answers "who called this lead, when". **Highest-write table → month-partitioned.** Inbound sync is **asynchronous**: the log is accepted instantly to a queue, a background worker matches it to a lead (normalized `phone_e164` lookup + atomic insert-or-attach) — so the caller's app never blocks on lead-matching (the existing system's choke point). |

**Provenance & transfers:** `provenance` on each engagement governs the staff-transfer default
— `company_sourced` stays with the company; `rep_self_sourced` may move with the rep — while a
manager can still choose per lead. Transfers are recorded as new `ownership_assignments` rows.

---

## 5. Cluster D — Sources, campaigns & attribution (profitability)

| Table | Purpose / key columns |
|---|---|
| `sources` | Channel catalogue: Meta, Google, website, 99acres/MagicBricks, walk-in, call, manual-Excel, salesperson-entry, API. |
| `campaigns` | Created by SBM. `company_id`, `name`, `utm_source`/`utm_medium`/`utm_campaign`, status, dates. |
| `campaign_costs` | **Manual cost entries by SBM** (`amount`, `currency`, `period_start`/`period_end`). The cost side of profitability. |
| `ingestion_keys` | **Per-campaign API key/endpoint.** External sources POST leads in; each lead is auto-attributed to that campaign/source. Idempotency + rate-limited. |

**Profitability:** every engagement carries its `source_id`/`campaign_id`; when an engagement
reaches a "won/booked" stage we can trace the **sale back to the campaign**, and compare
against `campaign_costs` to compute cost-per-lead and ROI (multi-currency aware — see §8).

---

## 6. Cluster E — Pipeline, dispositions, SLA & routing

| Table | Purpose / key columns |
|---|---|
| `pipeline_stages` | Configurable funnel **per company** (shared default set, may diverge later). Confirmed set: **New → Contacted → Qualified → Site Visit → Negotiation → Token/Advance → Loan/Documentation in Process → Booked (Won) → Lost**, plus parked **Cold/Future Prospect** and proposed terminal **Booking Cancelled** (separate from Lost). |
| `dispositions` | Call/interaction outcomes in **4 groups**, each flagged active-vs-close: **connected-positive** (Interested, Call Back Requested, Site Visit Scheduled, Ready to Book) · **connected-negative** (Not Interested, Budget/Location Mismatch, Already Purchased, Just Enquiring → close) · **not-connected** (Not Answering, Not Reachable, Switched Off, Busy → stay active, feed SLA) · **invalid** (Invalid Number, Wrong Number, Wrong Enquiry, DND → close). Kept **separate** from stages, activity-types, and sources (the existing system conflated all four in one flat `tasks` list). |
| `sla_policies` | Per company/stage time limits + escalation targets. **Clock starts at lead creation** (even while Unassigned in a pool); breach **escalates to the current pool's manager** (BH/TH) to allocate. Respects each company's **timezone + working calendar** (no off-hours escalation). |
| `routing_policies` | The reusable assignment recipe (see `docs/LEAD-INTAKE.md` §3). `company_id`, `name`, `mode` (fixed / round_robin / weighted / capacity / attribute / sourcing_sp; territory/project/performance/score/availability = later), `scope_level` (branch/team/sp), targets as child rows `{target_type, target_id, weight}`, round-robin cursor, caps & fallback config. **Every source / campaign / web form / API key / import batch references a `routing_policy_id`** (its preset); managers can also run a policy over an unallocated pool. v1 modes: round_robin / weighted / capacity / attribute / fixed / sourcing_sp + global-pool (pull/claim). |
| `routing_rules` | The **returning-lead ownership rule** (separate from the preset policy): (1) open engagement → current owner (continuity); (2) re-inquiry on a closed/dormant lead → highest-intent recent owner within the time window (site-visit outranks a call); (3) outside window / no meaningful prior activity → channel preset / normal routing; (4) prior owner left → Team Lead / round-robin. Window **tiered by intent**, **admin-configurable per company** (stored as settings, not hardcoded) — starting defaults **45/30/15 days** (site-visit-or-negotiation / contacted / never-reached) for the sub-1-month buying cycle. |

---

## 7. Cluster F — Trust: compliance & audit

| Table | Purpose / key columns |
|---|---|
| `audit_logs` | **Immutable** who-changed-what-when, especially lead ownership (settles commission disputes). Append-only. |
| `consents` | Consent + retention tracking for DPDP/PDPL (purpose, timestamp, source). |
| (all tables) | **Soft delete** (`deleted_at`) — leads are never physically deleted. Encryption at rest; PII access logged. |

---

## 8. Cluster G — Multi-country, comms & phased modules

- **Time:** every timestamp stored in **UTC**; displayed in each company's timezone; SLA timers
  use the company's working calendar.
- **Money:** costs/sale values stored **with their currency**; group roll-ups convert via stored
  FX rates.
- **Phone:** country-aware E.164 normalization (India `+91`, UAE `+971`) is the dedup backbone.
- **`communications`** (phased after core): WhatsApp/SMS/call/email logged against an
  engagement, feeding the history.
- **Lead scoring** (later module): schema leaves room; built after core is stable.

---

## 9. How each key requirement is satisfied

| Requirement | Mechanism |
|---|---|
| No duplicate leads | `UNIQUE(phone_e164)` on an unpartitioned master + atomic insert-or-attach |
| Globally unique lead, company-private history | Shared `leads` master + `lead_engagements` scoped by `company_id` (application-layer tenant scope) |
| Group sees all, company sees own | Application-layer tenant scope keyed to the request's company context; group roles bypass (MySQL has no native RLS) |
| Multiple numbers / households | `lead_phones` + manual `lead_links` (no risky auto-merge) |
| Staff transfers + provenance | SCD2 `user_assignments` & `ownership_assignments` + `provenance` flag |
| Campaign profitability | `source_id`/`campaign_id` on engagements + `campaign_costs` + won-stage attribution |
| Speed-to-lead | `sla_policies` + escalation jobs, timezone-aware |
| Scale to tens of millions | Master **unpartitioned + strongly indexed** (point lookups are made instant by the index on `phone_e164`, not by partitioning); big append tables (`call_logs`, `follow_ups`/activities, `lead_engagements`) **partitioned by month**; phone **hash / last-two-digit** partitioning of the master held *in reserve* for proven write-contention (NOT first-two-digit — Indian mobiles cluster on 9/8/7/6 → skewed partitions); analytics on read replica + **summary/rollup tables (or OCI MySQL HeatWave)**; dedicated search engine; **connection pooling (ProxySQL / MySQL Router / OCI-managed)**; bulk imports via background job queues |
| High-concurrency call sync (existing choke point) | Normalized indexed `phone_e164` (kills full-table phone scans) + **async queue + background lead-matcher** + **atomic insert-or-attach** (`ON DUPLICATE KEY UPDATE`, no SELECT-then-INSERT race) + connection pooler + optional Redis `phone→lead_id` cache |
| Multi-country | per-company timezone/currency/calendar/language; UTC storage; E.164 |
| Compliance & disputes | `audit_logs`, `consents`, soft deletes, encryption |

---

## 10. Research scorecard (what backed these choices)

- **Confirmed (high confidence):** E.164 derived-field dedup; field-level survivorship;
  shared global master with per-tenant scoping; activity-preserving Lead→Account/Contact model;
  SCD2 effective-dating; partition history but not the identity master
  (unique-key-includes-partition-key rule). *(The research validated this with PostgreSQL RLS;
  on MySQL the same isolation is enforced at the application layer — see decision #20.)*
- **Refuted (so we deliberately avoid):** "you must use fuzzy/AI matching"; specific auto-merge
  confidence thresholds. → We keep exact-phone auto-dedup + **manual** linking.
- **Not independently verified (handled via standard practice, confirm with client):** exact
  DPDP/PDPL clauses & data residency, real-estate disposition taxonomy, ROI formulas,
  multi-currency specifics.

---

## 11. Open items for the user / client

1. **Pipeline stages & dispositions** — confirm HiLITE's actual funnel stages and call outcomes
   (proposed defaults above), or get the real list from the client.
2. **Data residency** — legal check: is a single OCI region acceptable, or must UAE data stay
   in-region?
3. **Returning-lead ownership rule** — confirm the exact "who gets a re-inquiry" logic and the
   "reasonable time window".
