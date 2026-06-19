# Lead Intake & Assignment — Design (for confirmation)

> How leads enter from every channel and how they get assigned. One shared pipeline; one
> flexible assignment model. **Status: CONFIRMED 2026-06-05.** v1 strategy set = round-robin,
> weighted, capacity, attribute/segment-based, + global pool, push & pull, guardrails, fallback.
> SLA clock starts at lead creation (escalates to the pool's manager while unallocated).

---

## 1. One pipeline, every channel

All channels — CallSync, campaign API, web forms, portals (99acres/MagicBricks), walk-ins,
manual entry, bulk Excel — converge into **one intake pipeline** so dedup/attribution/assignment
live in exactly one place:

```
ANY CHANNEL → 1. NORMALIZE phone → E.164
            → 2. DEDUP: atomic insert-or-attach on phone_e164 (global master lead)
            → 3. ATTRIBUTE: stamp source + campaign (+ UTM, callsync_auto tag, etc.)
            → 4. ENGAGEMENT: find/create THIS company's engagement with the master
            → 5. ASSIGN: channel preset → (returning-lead window?) → routing → owner path
            → 6. ACTIVATE: start SLA timer, notify owner, auto-create first task
```

Each channel is only responsible for getting data to step 1 in the right shape; CallSync workers,
the campaign API, and the bulk-import job all call the **same** steps 2–6.

---

## 2. The assignment model — a 3-level hierarchy path

Every engagement (a company's lead) carries an **owner path**, filled top-down, any level optional:

| Level | Who | Field |
|---|---|---|
| Branch | Branch Head (BH) | `assigned_branch_id` |
| Team | Team Lead/Head (TH) | `assigned_team_id` |
| Salesperson | SP | `assigned_user_id` |

**Rules**
- The **deepest filled level = current responsible party.**
- Levels **below** the deepest filled = **unallocated pool**, owned by the manager at that level.
- Examples:
  - Branch set, team+SP empty → **branch unallocated pool** (BH decides: push to a team, an SP, or run a rule).
  - Team set, SP empty → **team unallocated pool** (TH assigns to an SP).
  - SP set → fully allocated; that SP owns it.
  - **No level set → Unassigned:** the lead rests in the **company-wide global pool** (anyone
    permitted can claim, or a manager distributes) — a valid resting state, not an error.
- Every change to the path is written to **`ownership_assignments` (SCD2)** — level, target, reason,
  who did it, when — so "who held this lead, at what level, when" is never lost.

**Manager pool management:** a BH/TH sees their unallocated pool and can either hand-pick leads to
people below, or **run a routing rule over the whole pool** to distribute in bulk.

---

## 3. The full assignment catalogue

A **channel preset** (set when a source/campaign/form/API key/import is created) **or** a manager
distributing a pool picks from this same catalogue. Stored as a `routing_policy`.

### 3.1 Target types (where a lead can land)
1. A specific **SP**
2. A **Team** → TH pool
3. A **Branch** → BH pool
4. **Unassigned / global pool** — no BH/TH/SP; company-wide holding area (claim or distribute later)
5. A **custom set** — hand-picked SPs/teams/branches
6. **Auto-route** over a pool (strategy + scope below)

### 3.2 Routing strategies (how auto-route picks within a pool)
- **Round-robin** — even rotation *(v1)*
- **Weighted** — admin-set weights *(v1, confirmed)*
- **Capacity-based** — fewest open (non-closed) leads *(v1, confirmed)*
- **Attribute/segment-based** — by a lead property (NRI, language, city, budget, project) → e.g.
  NRI/Arabic → NRI desk *(v1 — valuable given the NRI segment)*
- **Territory/geography** — by pincode/city/region *(later; suits Builders=north / Properties=south)*
- **Product/project-based** — by the project the lead wants *(later)*
- **Performance-based** — weights derived from conversion stats *(later; needs analytics)*
- **Lead-score-based** — hot → senior closers *(later; needs scoring module)*
- **Availability/shift-based** — only SPs on shift/online *(later; needs presence; matters for Dubai tz)*

### 3.3 Pool scope (the set a strategy runs over — any level × any breadth)
- SPs of one **team**
- SPs of a **branch** (across its teams)
- SPs across the whole **company** (across branches)
- **All THs** of a branch / **all BHs** of the company (route to managers, who sub-allocate)
- A **custom** hand-picked set

### 3.4 Push vs Pull
- **Push** (default) — system assigns to a specific person.
- **Pull / claim** — leads sit in a pool; reps claim them (enables the global pool). Guard against
  cherry-picking (claim window / limited visibility).
- **Hybrid** — pooled with "claim within X minutes, else auto-assign."

### 3.5 Channel-preset modes (special cases)
- **Sourcing SP (CallSync):** owner = the **calling SP** (fixed-to-caller); always wins.
- Otherwise a channel preset = any target type (3.1) + strategy (3.2) + scope (3.3).

### 3.6 Guardrails (apply to any strategy)
- **Caps** — max open leads per SP; overflow → pool / next eligible
- **Skip ineligible** — round-robin skips capped/offline reps
- **Sticky returning-lead** — prior owner within the window (decided)
- **No-reassign-if-open** — never yank an actively-owned lead
- **Manual override** — managers reassign anytime (SCD2 logged)
- **Re-route on staff exit** — owner leaves → pool / TL (decided)

### 3.7 Fallback chain
If a strategy can't place a lead (all capped/offline) → drop to the **unassigned global pool**
and/or escalate to the manager. Always defined, so leads never vanish.

### 3.8 Recommended phasing
- **Build now (v1):** target types 1–5; strategies round-robin / weighted / capacity / attribute-based;
  pool scope all breadths; push + pull (global pool); guardrails (caps, sticky, manual override,
  fallback); SLA escalation.
- **Later:** territory/geo, product/project, performance-based, lead-score-based, availability/shift.

---

## 4. How each door behaves by default

| Channel | Default assignment |
|---|---|
| **CallSync (SP calling)** | Always to the **calling SP** (provenance = `rep_self_sourced`). |
| **Web form / manual entry** | Preset per form: creator, a fixed person, or a routing rule. |
| **Bulk Excel import** | At import time choose target **level** (BH → branch pool, TH → team pool, or SP) **or** a routing rule. Partial assignment supported. |
| **Campaign / portal / API** | Preset at setup: fixed target or routing rule, at any level. |

---

## 5. Interactions with rules already decided

- **Returning-lead window wins for continuity:** a re-inquiry **within** the tiered window goes to
  the **prior owner**, bypassing the channel preset (don't re-route an active relationship).
  **Outside** the window → the channel preset/routing applies as for a fresh lead.
- **Provenance:** CallSync/SP-sourced = `rep_self_sourced`; campaign/import/company sources =
  `company_sourced` — drives the staff-transfer rules (decision #9).
- **Cross-company (option a):** if the master already belongs to Company A and the lead arrives via
  Company B, B gets its **own private engagement** with its own owner path; A is untouched/unseen.
- **callsync_auto tag:** leads auto-created from unknown calls are tagged so they don't pollute
  campaign profitability.

---

## 6. Open sub-decisions (flagging for you)

1. **SLA clock while unallocated:** **CONFIRMED — starts at lead creation**, even while sitting in
   a BH/TH/global pool; breach escalates to the current pool's manager to allocate.
2. **Weighted basis:** admin-set fixed weights to start (performance-informed later). — **CONFIRMED yes.**
3. **Capacity basis:** "open load" = count of the SP's engagements not in a closed stage. — **CONFIRMED yes.**

---

## 7. Schema implications (to fold into DATA_MODEL)

- `lead_engagements`: add `assigned_branch_id`, `assigned_team_id`, `assigned_user_id` (nullable,
  top-down); the deepest non-null = responsible party.
- `ownership_assignments` (SCD2): extend with `level` (branch/team/sp), `target_id`, `reason`,
  `assigned_by`, `valid_from`/`valid_to`.
- `routing_policies`: `id`, `company_id`, `name`, `mode` (fixed/round_robin/weighted/capacity/territory),
  `scope_level` (branch/team/sp), targets (child rows of `{target_type, target_id, weight}`),
  round-robin cursor for fair rotation.
- Sources / campaigns / web forms / API keys / import batches each reference a `routing_policy_id`
  (their preset).
